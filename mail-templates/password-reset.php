<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" 
        content="width=device-width, initial-scale=1.0">
  <title>Password Reset</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .header {
      text-align: center;
      padding: 10px 0;
    }
    .content {
      padding: 20px;
      text-align: center;
    }
    .button {
      display: inline-block;
      padding: 10px 20px;
      margin-top: 20px;
      background-color: #0073aa;
      color: #ffffff;
      text-decoration: none;
      border-radius: 5px;
    }
    .footer {
      text-align: center;
      padding: 10px 0;
      font-size: 12px;
      color: #777777;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1> Gracias por registrarte! {{user_displayname}}</h1>
      <p>
        Hemos creado tu cuenta en <a href="{{site_url}}"> {{site_name}} </a>
      </p>
    </div>
    <div class="content">
      <p>
        Para establecer tu contraseña, por favor haz clic en el siguiente enlace:
      </p>
      <a href="{{reset_link}}" class="button"> Establecer contraseña </a>
    </div>
  </div>
</body>
</html>