<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Error 500 - Ocurrió un problema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Fuente -->
  <link href="https://fonts.googleapis.com/css?family=Montserrat:700,900" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    .container {
      text-align: center;
      z-index: 2;
      max-width: 800px;
    }

    /* Imagen de advertencia */
    .warning-img {
      width: 120px;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
    }

    h1 {
      font-size: 10rem;
      margin: 0;
      position: relative;
      display: inline-block;
      animation: pulse 1.5s infinite;
      text-shadow: 4px 4px 15px rgba(0,0,0,0.5);
    }

    h1 i {
      color: #ff4d4d;
      animation: shake 2s infinite;
    }

    h2 {
      font-size: 2rem;
      margin: 20px 0;
    }

    p {
      font-size: 1.2rem;
      margin-bottom: 30px;
      color: #ddd;
    }

    a {
      text-decoration: none;
      background: #ff4d4d;
      color: white;
      padding: 12px 30px;
      border-radius: 30px;
      font-weight: bold;
      transition: 0.3s;
    }

    a:hover {
      background: #e60000;
    }

    .error-details {
      background: rgba(0,0,0,0.6);
      color: #ffcccc;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      text-align: left;
      font-size: 0.9rem;
      overflow-x: auto;
      max-height: 200px;
    }

    /* Animaciones */
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
    }

    /* Fondo animado */
    .bubbles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      overflow: hidden;
    }

    .bubbles span {
      position: absolute;
      display: block;
      width: 20px;
      height: 20px;
      background: rgba(255,255,255,0.2);
      border-radius: 50%;
      animation: rise 10s linear infinite;
      bottom: -150px;
    }

    .bubbles span:nth-child(1) { left: 10%; animation-duration: 8s; animation-delay: 0s; }
    .bubbles span:nth-child(2) { left: 20%; animation-duration: 10s; animation-delay: 2s; }
    .bubbles span:nth-child(3) { left: 35%; animation-duration: 12s; animation-delay: 4s; }
    .bubbles span:nth-child(4) { left: 50%; animation-duration: 15s; animation-delay: 3s; }
    .bubbles span:nth-child(5) { left: 70%; animation-duration: 8s; animation-delay: 5s; }
    .bubbles span:nth-child(6) { left: 85%; animation-duration: 12s; animation-delay: 1s; }

    @keyframes rise {
      0% { transform: translateY(0) scale(0); }
      100% { transform: translateY(-1000px) scale(1); }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Imagen de advertencia -->
    <img src="https://cdn-icons-png.flaticon.com/512/564/564619.png" alt="Advertencia" class="warning-img">

    <h1><i class="fas fa-bug"></i> 500</h1>
    <h2>¡Ups! Algo salió mal en el servidor</h2>
    <p>No es tu culpa, estamos trabajando para solucionarlo.</p>
    <a href="{{ url('/') }}">Volver al inicio</a>

    {{-- Mostrar detalles SOLO en desarrollo 
    @if(isset($exception))
      <div class="error-details">
        <strong>Detalles del error:</strong><br>
        {{ $exception->getMessage() }}
      </div>
    @endif
  </div>
--}}
  <div class="bubbles">
    <span></span><span></span><span></span>
    <span></span><span></span><span></span>
  </div>
</body>
</html>
