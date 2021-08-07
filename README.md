#Modo de uso

A continuación se especifican los pasos necesarios y pre-requisitos para la ejecución de la presente herramienta de migración de posts

#Pre-requisitos
-1 Tener montadas localmente tanto la base de datos del sitio original como la del sitio destino de la información

#Procedimiento de uso
-1 Crear un nuevo archivo de configuración a partir del archivo "Configuration.ex.php" llamandolo "Configuration.php"
-2 En la sección DB_ORIGEN de dicho archivo, configurar los datos de conexión de la base de datos del sitio de origen
-3 En la sección DB_DESTINO de dicho archivo, configurar los datos de conexión de la base de datos del sitio de destino
-4 Configurar los directorios en los cuales se encuentran los archivos multimedia
-5 Ejecutar el script con el comando "php migrate_post_to_wordpress.php" 
