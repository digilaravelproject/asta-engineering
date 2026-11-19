<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #172033; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #2f9f96;">{{ $subjectLine }}</h2>
        <div>{!! nl2br(e($body)) !!}</div>
        <p style="margin-top: 28px;">Regards,<br>ASTA Engineering</p>
    </div>
</body>
</html>
