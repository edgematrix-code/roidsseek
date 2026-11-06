/**
 * payment-qr.js - tiny self-contained QR Code generator (byte mode, level M).
 *
 * Used by the crypto payment page to show a scannable code for the receive
 * address without any external request, dependency or build step.
 *
 *   CryptoQR.matrix('0xabc...')  -> { size: 25, modules: [[true, false, ...]] }
 *   CryptoQR.svg('0xabc...', { scale: 5, margin: 2 }) -> '<svg ...></svg>'
 *
 * Supports versions 1-10 (up to ~200 bytes), which is far more than a wallet
 * address or BIP-21/EIP-681 link needs.
 */
(function (root) {
    'use strict';

    // EC level M block layout: [ec codewords per block, [[block count, data codewords], ...]]
    var BLOCKS = {
        1: { ec: 10, groups: [[1, 16]] },
        2: { ec: 16, groups: [[1, 28]] },
        3: { ec: 26, groups: [[1, 44]] },
        4: { ec: 18, groups: [[2, 32]] },
        5: { ec: 24, groups: [[2, 43]] },
        6: { ec: 16, groups: [[4, 27]] },
        7: { ec: 18, groups: [[4, 31]] },
        8: { ec: 22, groups: [[2, 38], [2, 39]] },
        9: { ec: 22, groups: [[3, 36], [2, 37]] },
        10: { ec: 26, groups: [[4, 43], [1, 44]] }
    };

    var ALIGN = {
        1: [], 2: [6, 18], 3: [6, 22], 4: [6, 26], 5: [6, 30],
        6: [6, 34], 7: [6, 22, 38], 8: [6, 24, 42], 9: [6, 26, 46], 10: [6, 28, 50]
    };

    // ---------------------------------------------------------------- GF(256)
    var EXP = new Array(512);
    var LOG = new Array(256);
    (function buildTables() {
        var x = 1;
        for (var i = 0; i < 255; i++) {
            EXP[i] = x;
            LOG[x] = i;
            x <<= 1;
            if (x & 0x100) { x ^= 0x11D; }
        }
        for (var j = 255; j < 512; j++) { EXP[j] = EXP[j - 255]; }
    })();

    function gfMul(a, b) {
        return (a === 0 || b === 0) ? 0 : EXP[LOG[a] + LOG[b]];
    }

    function rsGenerator(degree) {
        var poly = [1];
        for (var i = 0; i < degree; i++) {
            var next = new Array(poly.length + 1);
            for (var k = 0; k < next.length; k++) { next[k] = 0; }
            for (var j = 0; j < poly.length; j++) {
                next[j] ^= poly[j];
                next[j + 1] ^= gfMul(poly[j], EXP[i]);
            }
            poly = next;
        }
        return poly;
    }

    function rsRemainder(data, degree) {
        var gen = rsGenerator(degree);
        var res = [];
        for (var i = 0; i < degree; i++) { res.push(0); }
        for (var n = 0; n < data.length; n++) {
            var factor = data[n] ^ res[0];
            res.shift();
            res.push(0);
            for (var j = 0; j < degree; j++) {
                res[j] ^= gfMul(gen[j + 1], factor);
            }
        }
        return res;
    }

    // ------------------------------------------------------------ data encode
    function utf8Bytes(text) {
        var out = [];
        for (var i = 0; i < text.length; i++) {
            var c = text.charCodeAt(i);
            if (c < 0x80) {
                out.push(c);
            } else if (c < 0x800) {
                out.push(0xC0 | (c >> 6), 0x80 | (c & 63));
            } else if (c >= 0xD800 && c <= 0xDBFF && i + 1 < text.length) {
                var lo = text.charCodeAt(++i);
                var cp = 0x10000 + ((c - 0xD800) << 10) + (lo - 0xDC00);
                out.push(0xF0 | (cp >> 18), 0x80 | ((cp >> 12) & 63), 0x80 | ((cp >> 6) & 63), 0x80 | (cp & 63));
            } else {
                out.push(0xE0 | (c >> 12), 0x80 | ((c >> 6) & 63), 0x80 | (c & 63));
            }
        }
        return out;
    }

    function dataCodewordCount(version) {
        var layout = BLOCKS[version];
        var total = 0;
        for (var i = 0; i < layout.groups.length; i++) {
            total += layout.groups[i][0] * layout.groups[i][1];
        }
        return total;
    }

    function pickVersion(byteLength) {
        for (var v = 1; v <= 10; v++) {
            var countBits = v < 10 ? 8 : 16;
            if (4 + countBits + byteLength * 8 <= dataCodewordCount(v) * 8) {
                return v;
            }
        }
        return 0;
    }

    function codewords(bytes, version) {
        var layout = BLOCKS[version];
        var totalData = dataCodewordCount(version);
        var bits = [];

        function push(value, length) {
            for (var k = length - 1; k >= 0; k--) { bits.push((value >> k) & 1); }
        }

        push(4, 4); // byte mode
        push(bytes.length, version < 10 ? 8 : 16);
        for (var i = 0; i < bytes.length; i++) { push(bytes[i], 8); }

        var capacity = totalData * 8;
        var terminator = Math.min(4, capacity - bits.length);
        push(0, terminator);
        while (bits.length % 8 !== 0) { bits.push(0); }

        var words = [];
        for (var b = 0; b < bits.length; b += 8) {
            var value = 0;
            for (var j = 0; j < 8; j++) { value = (value << 1) | bits[b + j]; }
            words.push(value);
        }
        var padIndex = 0;
        while (words.length < totalData) {
            words.push(padIndex % 2 === 0 ? 0xEC : 0x11);
            padIndex++;
        }

        var blocks = [];
        var offset = 0;
        for (var g = 0; g < layout.groups.length; g++) {
            var count = layout.groups[g][0];
            var length = layout.groups[g][1];
            for (var n = 0; n < count; n++) {
                var chunk = words.slice(offset, offset + length);
                offset += length;
                blocks.push({ data: chunk, ecc: rsRemainder(chunk, layout.ec) });
            }
        }

        var longest = 0;
        for (var i2 = 0; i2 < blocks.length; i2++) {
            longest = Math.max(longest, blocks[i2].data.length);
        }

        var result = [];
        for (var idx = 0; idx < longest; idx++) {
            for (var b2 = 0; b2 < blocks.length; b2++) {
                if (idx < blocks[b2].data.length) { result.push(blocks[b2].data[idx]); }
            }
        }
        for (var e = 0; e < layout.ec; e++) {
            for (var b3 = 0; b3 < blocks.length; b3++) { result.push(blocks[b3].ecc[e]); }
        }
        return result;
    }

    // ---------------------------------------------------------------- matrix
    function bit(value, index) {
        return ((value >>> index) & 1) !== 0;
    }

    function build(version, data, mask) {
        var size = version * 4 + 17;
        var modules = [];
        var isFunction = [];
        for (var y = 0; y < size; y++) {
            modules.push(new Array(size));
            isFunction.push(new Array(size));
            for (var x = 0; x < size; x++) {
                modules[y][x] = false;
                isFunction[y][x] = false;
            }
        }

        function setFn(x, y2, dark) {
            modules[y2][x] = !!dark;
            isFunction[y2][x] = true;
        }

        // Timing patterns.
        for (var t = 0; t < size; t++) {
            setFn(6, t, t % 2 === 0);
            setFn(t, 6, t % 2 === 0);
        }

        // Finder patterns (with separators).
        function finder(cx, cy) {
            for (var dy = -4; dy <= 4; dy++) {
                for (var dx = -4; dx <= 4; dx++) {
                    var dist = Math.max(Math.abs(dx), Math.abs(dy));
                    var px = cx + dx;
                    var py = cy + dy;
                    if (px >= 0 && px < size && py >= 0 && py < size) {
                        setFn(px, py, dist !== 2 && dist !== 4);
                    }
                }
            }
        }
        finder(3, 3);
        finder(size - 4, 3);
        finder(3, size - 4);

        // Alignment patterns.
        var align = ALIGN[version];
        for (var ai = 0; ai < align.length; ai++) {
            for (var aj = 0; aj < align.length; aj++) {
                if ((ai === 0 && aj === 0) || (ai === 0 && aj === align.length - 1) || (ai === align.length - 1 && aj === 0)) {
                    continue;
                }
                for (var ady = -2; ady <= 2; ady++) {
                    for (var adx = -2; adx <= 2; adx++) {
                        setFn(align[aj] + adx, align[ai] + ady, Math.max(Math.abs(adx), Math.abs(ady)) !== 1);
                    }
                }
            }
        }

        // Format areas are reserved with mask 0; the real bits are written later.
        drawFormat(0, false);

        // Version information (versions 7+).
        if (version >= 7) {
            var rem = version;
            for (var v = 0; v < 12; v++) {
                rem = (rem << 1) ^ ((rem >>> 11) * 0x1F25);
            }
            var vbits = (version << 12) | rem;
            for (var vi = 0; vi < 18; vi++) {
                var vb = bit(vbits, vi);
                var a = size - 11 + (vi % 3);
                var b4 = Math.floor(vi / 3);
                setFn(a, b4, vb);
                setFn(b4, a, vb);
            }
        }

        // Data modules, zig-zag from the bottom right.
        var i = 0;
        var totalBits = data.length * 8;
        for (var right = size - 1; right >= 1; right -= 2) {
            if (right === 6) { right = 5; }
            for (var vert = 0; vert < size; vert++) {
                for (var pair = 0; pair < 2; pair++) {
                    var px2 = right - pair;
                    var upward = ((right + 1) & 2) === 0;
                    var py2 = upward ? size - 1 - vert : vert;
                    if (!isFunction[py2][px2] && i < totalBits) {
                        modules[py2][px2] = bit(data[i >>> 3], 7 - (i & 7));
                        i++;
                    }
                }
            }
        }

        applyMask(mask);
        drawFormat(mask, false);

        function applyMask(maskType) {
            for (var my = 0; my < size; my++) {
                for (var mx = 0; mx < size; mx++) {
                    if (isFunction[my][mx]) { continue; }
                    var invert;
                    switch (maskType) {
                        case 0: invert = (mx + my) % 2 === 0; break;
                        case 1: invert = my % 2 === 0; break;
                        case 2: invert = mx % 3 === 0; break;
                        case 3: invert = (mx + my) % 3 === 0; break;
                        case 4: invert = (Math.floor(mx / 3) + Math.floor(my / 2)) % 2 === 0; break;
                        case 5: invert = (mx * my) % 2 + (mx * my) % 3 === 0; break;
                        case 6: invert = ((mx * my) % 2 + (mx * my) % 3) % 2 === 0; break;
                        default: invert = ((mx + my) % 2 + (mx * my) % 3) % 2 === 0; break;
                    }
                    if (invert) { modules[my][mx] = !modules[my][mx]; }
                }
            }
        }

        function drawFormat(maskType, onlyScore) {
            // EC level M === 0
            var dataBits = (0 << 3) | maskType;
            var remainder = dataBits;
            for (var k = 0; k < 10; k++) {
                remainder = (remainder << 1) ^ ((remainder >>> 9) * 0x537);
            }
            var bits = ((dataBits << 10) | remainder) ^ 0x5412;

            for (var i5 = 0; i5 <= 5; i5++) { setFn(8, i5, bit(bits, i5)); }
            setFn(8, 7, bit(bits, 6));
            setFn(8, 8, bit(bits, 7));
            setFn(7, 8, bit(bits, 8));
            for (var i6 = 9; i6 < 15; i6++) { setFn(14 - i6, 8, bit(bits, i6)); }

            for (var i7 = 0; i7 < 8; i7++) { setFn(size - 1 - i7, 8, bit(bits, i7)); }
            for (var i8 = 8; i8 < 15; i8++) { setFn(8, size - 15 + i8, bit(bits, i8)); }
            setFn(8, size - 8, true);

            if (onlyScore) { return; }
        }

        return { size: size, modules: modules, isFunction: isFunction };
    }

    // --------------------------------------------------------------- penalty
    function penalty(modules, size) {
        var score = 0;
        var x;
        var y;

        // Rule 1: runs of five or more identical modules.
        for (y = 0; y < size; y++) {
            var runColor = modules[y][0];
            var runLength = 1;
            for (x = 1; x < size; x++) {
                if (modules[y][x] === runColor) {
                    runLength++;
                } else {
                    if (runLength >= 5) { score += 3 + (runLength - 5); }
                    runColor = modules[y][x];
                    runLength = 1;
                }
            }
            if (runLength >= 5) { score += 3 + (runLength - 5); }
        }
        for (x = 0; x < size; x++) {
            var colColor = modules[0][x];
            var colLength = 1;
            for (y = 1; y < size; y++) {
                if (modules[y][x] === colColor) {
                    colLength++;
                } else {
                    if (colLength >= 5) { score += 3 + (colLength - 5); }
                    colColor = modules[y][x];
                    colLength = 1;
                }
            }
            if (colLength >= 5) { score += 3 + (colLength - 5); }
        }

        // Rule 2: 2x2 blocks of one colour.
        for (y = 0; y < size - 1; y++) {
            for (x = 0; x < size - 1; x++) {
                var c = modules[y][x];
                if (c === modules[y][x + 1] && c === modules[y + 1][x] && c === modules[y + 1][x + 1]) {
                    score += 3;
                }
            }
        }

        // Rule 3: finder-like 1:1:3:1:1 patterns.
        var pattern = [true, false, true, true, true, false, true];
        function matches(line, start) {
            for (var p = 0; p < 7; p++) {
                if (line[start + p] !== pattern[p]) { return false; }
            }
            return true;
        }
        function lightRun(line, start, step, size2) {
            var seen = 0;
            for (var q = start; q >= 0 && q < size2 && seen < 4 && !line[q]; q += step) { seen++; }
            return seen >= 4;
        }
        for (y = 0; y < size; y++) {
            for (x = 0; x + 7 <= size; x++) {
                if (matches(modules[y], x)) {
                    if (lightRun(modules[y], x - 1, -1, size) || lightRun(modules[y], x + 7, 1, size)) {
                        score += 40;
                    }
                }
            }
        }
        for (x = 0; x < size; x++) {
            var column = [];
            for (y = 0; y < size; y++) { column.push(modules[y][x]); }
            for (y = 0; y + 7 <= size; y++) {
                if (matches(column, y)) {
                    if (lightRun(column, y - 1, -1, size) || lightRun(column, y + 7, 1, size)) {
                        score += 40;
                    }
                }
            }
        }

        // Rule 4: dark/light balance.
        var dark = 0;
        for (y = 0; y < size; y++) {
            for (x = 0; x < size; x++) {
                if (modules[y][x]) { dark++; }
            }
        }
        var total = size * size;
        var k = Math.ceil(Math.abs(dark * 20 - total * 10) / total) - 1;
        score += Math.max(0, k) * 10;

        return score;
    }

    // ------------------------------------------------------------------- API
    function matrix(text) {
        var bytes = utf8Bytes(String(text));
        var version = pickVersion(bytes.length);
        if (version === 0) {
            throw new Error('Text is too long for this QR generator.');
        }
        var data = codewords(bytes, version);

        var best = null;
        var bestScore = Infinity;
        for (var mask = 0; mask < 8; mask++) {
            var candidate = build(version, data, mask);
            var score = penalty(candidate.modules, candidate.size);
            if (score < bestScore) {
                bestScore = score;
                best = candidate;
            }
        }

        return { size: best.size, modules: best.modules, version: version, mask: bestScore === Infinity ? 0 : undefined };
    }

    function svg(text, options) {
        var opts = options || {};
        var scale = opts.scale || 5;
        var margin = opts.margin === undefined ? 2 : opts.margin;
        var dark = opts.dark || '#101418';
        var light = opts.light || '#ffffff';
        var label = opts.label || 'Payment address QR code';

        var result = matrix(text);
        var size = result.size;
        var full = (size + margin * 2) * scale;
        var path = '';

        for (var y = 0; y < size; y++) {
            for (var x = 0; x < size; x++) {
                if (result.modules[y][x]) {
                    path += 'M' + ((x + margin) * scale) + ' ' + ((y + margin) * scale) + 'h' + scale + 'v' + scale + 'h-' + scale + 'z';
                }
            }
        }

        return '<svg viewBox="0 0 ' + full + ' ' + full + '" width="' + full + '" height="' + full + '" '
            + 'role="img" aria-label="' + label + '" shape-rendering="crispEdges" focusable="false">'
            + '<rect width="' + full + '" height="' + full + '" fill="' + light + '"/>'
            + '<path d="' + path + '" fill="' + dark + '"/>'
            + '</svg>';
    }

    var api = { matrix: matrix, svg: svg };
    root.CryptoQR = api;
    if (typeof module === 'object' && module.exports) { module.exports = api; }
}(typeof window !== 'undefined' ? window : this));
