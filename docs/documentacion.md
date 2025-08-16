# documentacion del codigo

**Estructura de archivos**
Antares_travel/ `archivo base`
  db/ `versiones de la base de datos`  
  docs/ `documentacion del codigo y mas detalles`
  public/ `en cuaestion sobre su uso`
  src/ `archivos necesarios del sitio web`
    admin/ `aqui todo el modulo de administrador desde login hasta endpoints y vistas`
    auth/ `todo sobre inicio se sesion de usuarios en el sitio web`
    config/ `archivos de configuracion del sistema`
      conexion.php `archivo de conexion a la base de datos`
      routes.php `rutas de archivos del sistema`
    modules/ `modulos en especifico como funciones reservar` 
    views/ `vistas reutilizables de html`
  storage/ `datos cargados en la plataforma como perfiles de usuario, fotos dinamicos`
  vendor/ `dependencias y librerias php no modificables`
  index.php `punto de entrada del sitio web incluido la landing page`
  .gitignore `codigo para restringir el git commit de archivos innecesarios`
  composer.json `configuracion de composer, incluye configuracion de vendor`

*nota: si el archivo de codigo no esta documentado, significa que esta en fase de pruebas*

