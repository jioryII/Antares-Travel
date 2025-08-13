<?php
// index.php
// Página simple que muestra 1 persona a calificar (puedes adaptar target_person dinámicamente)
$target_person = "Juan Pérez";
$client_id = "454921920428-o397pan3nhq05ss36c64o4hov91416v4.apps.googleusercontent.com";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Calificar persona — Módulo con Google Sign-In</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:2rem;background:#f7f7fb;color:#0f172a}
    .card{background:white;padding:1.5rem;border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,0.06);max-width:720px;margin:0 auto}
    .stars{display:flex;gap:.5rem;cursor:pointer;font-size:2rem}
    .star{user-select:none;transition:transform .12s}
    .star:hover{transform:translateY(-4px)}
    textarea{width:100%;min-height:90px;padding:.6rem;border-radius:8px;border:1px solid #e6e9ee}
    button{background:#FF6B6B;color:white;border:0;padding:.7rem 1.1rem;border-radius:10px;font-weight:600;cursor:pointer}
    .small{font-size:.9rem;color:#6b7280}
    .row{display:flex;gap:1rem;align-items:center}
    .muted{color:#94a3b8;font-size:.85rem}
  </style>

  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
  <div class="card">
    <h2>Calificar a <em><?=htmlspecialchars($target_person)?></em></h2>
    <p class="muted">Para publicar la calificación debes seleccionar tu cuenta de Google y validar.</p>

    <div style="margin:1rem 0">
      <label class="small">Estrellas</label>
      <div id="stars" class="stars" role="radiogroup" aria-label="Estrellas">
        <?php for($i=1;$i<=5;$i++): ?>
          <div class="star" data-value="<?=$i?>" title="<?=$i?> estrella(s)">☆</div>
        <?php endfor; ?>
      </div>
      <input type="hidden" id="stars_value" value="0">
    </div>

    <div style="margin:1rem 0">
      <label class="small">Comentario</label>
      <textarea id="comment" placeholder="Escribe tu comentario (opcional)"></textarea>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center">
      <div id="g_id_onload"
           data-client_id="<?=$client_id?>"
           data-auto_select="false"
           data-callback="handleCredentialResponse">
      </div>

      <!-- El botón renderizado por Google --> 
      <div id="buttonDiv"></div>

      <div style="margin-left:1rem">
        <button id="submitBtn" disabled>Enviar calificación</button>
      </div>
    </div>

    <p class="small" style="margin-top:0.8rem">Tu identidad se valida con Google; solo se guarda el identificador único (sub) y el email/name para referencia.</p>
    <div id="message" class="small"></div>
  </div>

<script>
let selectedStars = 0;
const stars = document.querySelectorAll('.star');
const starsValueInput = document.getElementById('stars_value');
const submitBtn = document.getElementById('submitBtn');
let idToken = null; // se llenará al hacer sign-in

stars.forEach(s => {
  s.addEventListener('click', () => {
    selectedStars = parseInt(s.getAttribute('data-value'), 10);
    starsValueInput.value = selectedStars;
    updateStarsUI(selectedStars);
    checkEnableSubmit();
  });
});

function updateStarsUI(n){
  stars.forEach(st => {
    const v = parseInt(st.getAttribute('data-value'),10);
    st.textContent = v <= n ? '★' : '☆';
    st.style.color = v <= n ? '#F59E0B' : '';
  });
}

// GOOGLE: render button and callback
function handleCredentialResponse(response) {
  // response.credential contiene el ID token (JWT) que hay que enviar al servidor
  idToken = response.credential;
  document.getElementById('message').textContent = 'Conectado con Google — listo para enviar.';
  checkEnableSubmit();
}

window.onload = function(){
  // Renderiza el botón "Sign in with Google"
  google.accounts.id.initialize({
    client_id: '<?=$client_id?>',
    callback: handleCredentialResponse
  });
  google.accounts.id.renderButton(
    document.getElementById('buttonDiv'),
    { theme: 'outline', size: 'large' }  // opciones visuales
  );
  // No auto prompt for now (one-tap) para que el flujo sea explícito
};

// enviar la calificación
submitBtn.addEventListener('click', async () => {
  if (!idToken) { alert('Primero selecciona tu cuenta de Google.'); return; }
  const stars = parseInt(starsValueInput.value, 10);
  if (!stars || stars < 1) { alert('Elige al menos 1 estrella.'); return; }
  const comment = document.getElementById('comment').value;

  submitBtn.disabled = true;
  document.getElementById('message').textContent = 'Enviando...';

  try {
    const resp = await fetch('verify.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        id_token: idToken,
        target_person: '<?=addslashes($target_person)?>',
        stars,
        comment
      })
    });
    const text = await resp.text();
    if (resp.ok) {
      document.getElementById('message').textContent = 'Calificación enviada. Gracias ✨';
    } else {
      document.getElementById('message').textContent = 'Error: ' + text;
    }
  } catch (err) {
    document.getElementById('message').textContent = 'Error de conexión.';
  } finally {
    submitBtn.disabled = false;
  }
});

function checkEnableSubmit(){
  // Activar enviar solo si hay token y estrellas
  submitBtn.disabled = !(idToken && parseInt(starsValueInput.value,10) >= 1);
}
</script>
</body>
</html>
