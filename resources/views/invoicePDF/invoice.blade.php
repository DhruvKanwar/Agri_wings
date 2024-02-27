<!-- resources/views/pdf.blade.php -->

<html>

<head>
    <style>
        /* Define your CSS styles here */
    </style>
</head>

<body>
    <h1>Hello, PDF!</h1>
    <p>This is a sample PDF generated using barryvdh/laravel-dompdf in Laravel.</p>
    <div>
        <p> ID : {{ $id }}</p>

        <p>Invoice Number: {{ $invoice_number }}</p>
        <p>Customer Name: {{ $customer_name }}</p>

        <!-- Add more fields as needed -->
    </div>
</body>

</html>