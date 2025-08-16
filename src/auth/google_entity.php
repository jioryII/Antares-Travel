<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login con Google One Tap</title>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
  <h1>Bienvenido a mi sitio</h1>

  <div id="g_id_onload"
       data-client_id="454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com"
       data-auto_prompt="true"
       data-callback="handleCredentialResponse">
  </div>

  <script>
    function handleCredentialResponse(response) {
      // Aquí recibes un JWT firmado por Google
      console.log("Credenciales de Google:", response.credential);

      // Decodificar token para obtener nombre y avatar
      const data = JSON.parse(atob(response.credential.split('.')[1]));
      console.log("Usuario:", data);

      alert(`Hola ${data.name}, bienvenido!`);
    }
  </script>
</body>
</html>
