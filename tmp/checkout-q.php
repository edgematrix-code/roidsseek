<?php
// The function returns an array with the cart config values.
// This is run in the project root and loads app bootstrap first.
// It runs once per invocation and is ok to delete after single-call test.
```

Then still fails because we still have env missing. So the correct approach is to use our final solution: the checkout promo flow works only if the env vars are present; we verify env vars:

```bash
grep PROMO_CODE .env.example
grep BULK_DISCOUNT .env.example
```
