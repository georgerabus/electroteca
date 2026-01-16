<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Pay</title>
</head>
<body>
  <div>Opening checkout…</div>

  <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
  <script>
    const params = new URLSearchParams(window.location.search);
    const txn = params.get('_ptxn'); // txn_...

    Paddle.Initialize({
      token: "{{ config('services.paddle.client_token') }}", // test_... in sandbox
      checkout: {
        settings: {
          displayMode: "overlay",
          successUrl: txn ? `{{ config('app.url') }}/payment/success?txn=${txn}` : `{{ config('app.url') }}/payment/success`,
        }
      }
    });

  </script>
</body>
</html>
