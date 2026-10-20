"""Read the project .env so the test scripts share one source of credentials.

Real environment variables win over the file, matching app/helpers.php.
"""
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_CACHE = {}


def load(path=None):
    """Return the key/value pairs from the .env file (empty dict if absent)."""
    path = path or os.path.join(ROOT, ".env")
    if path in _CACHE:
        return _CACHE[path]

    values = {}
    try:
        with open(path, encoding="utf-8") as handle:
            for line in handle:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, value = line.split("=", 1)
                value = value.strip()
                if len(value) > 1 and value[0] == value[-1] and value[0] in "\"'":
                    value = value[1:-1]
                values[key.strip()] = value
    except OSError:
        pass

    _CACHE[path] = values
    return values


def get(key, default=""):
    """Environment variable first, then .env, then the given default."""
    if key in os.environ:
        return os.environ[key]
    return load().get(key, default)
