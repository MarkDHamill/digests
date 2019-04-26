<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2018 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(

	'PLURAL_RULE'											=> 1,

	'ACP_CAT_DIGESTS'										=> 'Resúmenes',
	'ACP_DIGESTS_SETTINGS'									=> 'Ajustes de resumen',
	'ACP_DIGESTS_GENERAL_SETTINGS'							=> 'Ajustes generales',
	'ACP_DIGESTS_GENERAL_SETTINGS_EXPLAIN'					=> 'Estos son los ajustes generales de los resúmenes. Ten en cuenta que si los resúmenes se deben entregar en una hora exacta, debes configurar y habilitar el <strong> <a href="https://wiki.phpbb.com/Modular_cron#Use_system_cron"> cron del sistema </a> de phpBB </ strong>. De lo contrario, la próxima vez que haya tráfico en el foro, se enviarán los resúmenes de las horas actuales y anteriores. Para obtener más información, consulta las preguntas frecuentes en los foros en phpbb.com.',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS'						=> 'Ajustes por defecto del usuario',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS_EXPLAIN'				=> 'Esta configuración permite a los administradores establecer los valores por defecto que ven los usuarios cuando se suscriben a un resumen.',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS'							=> 'Editar suscriptores',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS_EXPLAIN'					=> 'Esta página te permite ver quién está o no está recibiendo resúmenes. Puedes suscribir o anular la suscripción de forma selectiva, y editar todos los detalles del resumen o suscriptores individuales. Al marcar filas con la casilla de verificación en la primera columna, puedes suscribir a estos usuarios con valores por defecto o cancelar su suscripción. Para ello, selecciona los controles apropiados en la parte inferior de la página y luego presiona Enviar. También puedes usar estos controles para ordenar y filtrar la lista junto con el botón Actualizar.',
	'ACP_DIGESTS_BALANCE_LOAD'								=> 'Balancear la carga',
	'ACP_DIGESTS_BALANCE_LOAD_EXPLAIN'						=> 'Si muchos resúmenes se envian a ciertas horas, eso puede causar problemas de rendimiento, esto distribuye las suscripciones de los resúmenes para que el mismo número de resúmenes se envien para cada hora deseada. La siguiente tabla muestra el número actual y los nombres de los suscriptores para cada hora con <strong> horas superpobladas </ strong>. Esta función actualiza el resumen de envío de horas mínimamente. Se hacen cambios en las horas donde el número de suscriptores excede la carga normal, y solo para los suscriptores que exceden la media horaria de esa hora. <em> Precaución </ em>: los suscriptores pueden estar molestos porque sus horarios de suscripción han cambiado y pueden recibir una notificación por correo electrónico, dependiendo de la configuración en la configuración general de los resúmenes. Si quieres puedes restringir el equilibrio a un tipo especifico de resumen, balanceo para horas específicas y aplicar balanceo a horas específicas.',
	'ACP_DIGESTS_BALANCE_OPTIONS'							=> 'Opciones de balanceo',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'				=> 'Suscripción y baja en masa',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'		=> 'Esta función permite a los administradores suscribirse o cancelar la suscripción a todos los miembros de su foro de una sola vez. Las configuraciones predeterminadas de los resúmenes se utilizan para suscribir miembros. Si un miembro ya tiene una suscripción de resumen, una suscripción masiva conservará su configuración de resumen. No puedes especificar los foros que serán suscritos. Los usuarios se suscribirán a todos los foros a los que tengan acceso de lectura. <strong>Precaución</strong>: los suscriptores pueden estar molestos si están suscritos o cancelados sin su permiso.',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME'						=> 'Restablecer correo',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'				=> '',
	'ACP_DIGESTS_TEST'										=> 'Ejecutar manualmente el correo',
	'ACP_DIGESTS_TEST_EXPLAIN'								=> 'Esta función te permite ejecutar resúmenes manualmente para las pruebas iniciales y la resolución de problemas. También puedes usarlo para recrear resúmenes para una fecha y hora en particular. La zona horaria del foro se utiliza para calcular la fecha y la hora. Ten en cuenta que cuando se envían los resúmenes dependen del tráfico del foro, por lo que los resúmenes pueden llegar tarde para algunos usuarios. Esto se puede cambiar si configuras <a href="https://wiki.phpbb.com/Modular_cron#Use_system_cron"> un cron del sistema </a> y habilita la función phpBB <strong> cron del sistema </strong> . Para obtener más información, consulta las Preguntas frecuentes sobre la extensión Resúmenes en los foros en phpbb.com.',

	'LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE'					=> '<strong>Advertencia: el suscriptor %1$s tiene un tipo de resumen incorrecto de %2$s. Suponiendo que se quiere un resumen diario.</strong>',
	'LOG_CONFIG_DIGESTS_BAD_SEND_HOUR'						=> '<strong>La hora de envío del resumen de %1$s del usuario no es válida. Es %2$d. El numero debe ser >= 0 and < 24.</strong>',
	'LOG_CONFIG_DIGESTS_BALANCE_LOAD'						=> '<strong>Las cargas de balances de resúmenes se ejecutan exitosamente</strong>',
	'LOG_CONFIG_DIGESTS_BOARD_DISABLED'						=> '<strong>Se intentó ejecutar el envío del resumen, pero se detuvo porque el foro está deshabilitado.</strong>',
	'LOG_CONFIG_DIGESTS_CACHE_CLEARED'						=> '<strong>La carpeta store/phpbbservices/digests fue vaciada',
	'LOG_CONFIG_DIGESTS_CLEAR_SPOOL_ERROR'					=> '<strong>No se pueden borrar archivos en la carpeta store/phpbbservices/digests. Esto puede deberse a un problema de permisos o una ruta incorrecta. Los permisos de archivo en la carpeta deben configurarse en escritura pública (777 en sistemas basados en Unix).</strong>',
	'LOG_CONFIG_DIGESTS_CREATE_DIRECTORY_ERROR'				=> '<strong>No se puede crear la carpeta %s. Esto puede deberse a permisos insuficientes. Los permisos de archivo en la carpeta deben configurarse en escritura pública (777 en sistemas basados en Unix).</strong>',
	'LOG_CONFIG_DIGESTS_DUPLICATE_PREVENTED'				=> '<strong>NO se enviaron resúmenes a %1$s (%2$s) para la fecha %3$s y hora %4$02d UTC porque se envió uno a este suscriptor a principios de esta hora.</strong>',
	'LOG_CONFIG_DIGESTS_EDIT_SUBSCRIBERS'					=> '<strong>Editado los resúmenes de suscriptores</strong>',
	'LOG_CONFIG_DIGESTS_FILE_CLOSE_ERROR'					=> '<strong>No se puede cerrar el archivo %s</strong>',
	'LOG_CONFIG_DIGESTS_FILE_OPEN_ERROR'					=> '<strong>No se puede abrir un controlador de archivos en la carpeta %s. Esto puede deberse a permisos insuficientes. Los permisos de archivo en la carpeta deben configurarse en escritura pública (777 en sistemas basados en Unix).</strong>',
	'LOG_CONFIG_DIGESTS_FILE_WRITE_ERROR'					=> '<strong>No se puede escribir el archivo %s. Esto puede deberse a permisos insuficientes. Los permisos de archivo en la carpeta deben configurarse en escritura pública (777 en sistemas basados en Unix).</strong>', 
	'LOG_CONFIG_DIGESTS_FILTER_ERROR'						=> '<strong>El remitente de los resúmenes se llamó con un user_digest_filter_type inválido = %1$s para %2$s</strong>',
	'LOG_CONFIG_DIGESTS_FORMAT_ERROR'						=> '<strong>Se envió un resumen de correo con un user_digest_format inválido de %1$s para %2$s</strong>',
	'LOG_CONFIG_DIGESTS_GENERAL'							=> '<strong>Configuraciones generales de resúmenes alterados.</strong>',
	'LOG_CONFIG_DIGESTS_HOUR_RUN'							=> '<strong>Ejecutando resúmenes para %1$s en %2$02d UTC</strong>',
	'LOG_CONFIG_DIGESTS_INCONSISTENT_DATES'					=> '<strong>Ocurrió un error inusual. No se procesaron horas porque la última vez que se enviaron correctamente los resúmenes (marca de tiempo %1$d) se realizó después de que se ejecutaron los resúmenes (marca de tiempo %2$d).</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD'						=> '<strong>No se puede enviar un resumen a %1$s (%2$s). Este problema debe investigarse y solucionarse ya que probablemente significa que hay un problema general de envío de correos electrónicos.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL'				=> '<strong>No se puede enviar un resumen a %s. Este problema debe investigarse y solucionarse ya que probablemente significa que hay un problema general de envío de correos electrónicos.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD'						=> array(
		1 => '<strong>Un resumen fue %1$s %2$s (%3$s) para la fecha %4$s y hora %5$02d UTC que contiene %6$d mensaje y %7$d mensaje privado</strong>',
		2 => '<strong>Un resumen fue %1$s %2$s (%3$s) para la fecha %4$s y hora %5$02d UTC que contiene %6$d publicaciones y %7$d mensajes privados</strong>',
	),
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_DISK'				=> '<strong>Se escribió un resumen en store/phpbbservices/digests/%s. El resumen NO se envió por correo electrónico, pero se colocó aquí para su análisis.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL'			=> array(
		1 => '<strong>El resumen fue %1$s %2$s para la fecha %3$s y hora %4$02d UTC que contiene %5$d mensaje y %6$d mensaje privado</strong>',
		2 => '<strong>El resumen fue %1$s %2$s para la fecha %3$s y hora %4$02d UTC que contiene %5$d publicaciones y %6$d mensajes privados</strong>',
	),
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE'						=> '<strong>A digest was NOT sent to %1$s (%2$s) because user filters and preferences meant there was nothing to send</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE_NO_EMAIL'			=> '<strong>NO se envió un resumen a %s porque los filtros y preferencias del usuario significaban que no había nada que enviar</strong>',
	'LOG_CONFIG_DIGESTS_LOG_START'							=> '<strong>Comenzando el resumen de correo</strong>',
	'LOG_CONFIG_DIGESTS_LOG_END'							=> '<strong>Terminando resumen de correo</strong>',
	'LOG_CONFIG_DIGESTS_MAILER_RAN_WITH_ERROR'				=> '<strong>Se produjo un error mientras se estaba ejecutando el correo. Uno o más resúmenes pueden haber sido generados exitosamente.</strong>',
	'LOG_CONFIG_DIGESTS_MANUAL_RUN'							=> '<strong>Ejecución manual del remitente invocado.</strong>',
	'LOG_CONFIG_DIGESTS_MESSAGE'							=> '<strong>%s</strong>',	// Se utiliza para la depuración general, de lo contrario es difícil solucionar problemas en el modo cron.
	'LOG_CONFIG_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'			=> '<strong>Ejecutada una acción de suscripción o cancelación de suscripción de los resúmenes.</strong>',
	'LOG_CONFIG_DIGESTS_NO_ALLOWED_FORUMS'					=> '<strong>Advertencia: el suscriptor %s no tiene permisos de foro, por lo tanto, a menos que haya foros necesarios, los resúmenes nunca contendrán ningún contenido.</strong>',
	'LOG_CONFIG_DIGESTS_NO_BOOKMARKS'						=> '<strong>Advertencia: el suscriptor %s quiere temas marcados en su resumen pero no tiene temas marcados.</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_ERROR'					=> '<strong>No se puede enviar una notificación por email de los resúmenes generados por el administrador a %s</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_SENT'					=> '<strong>Se envió un correo electrónico a %1$s (%2$s) que indica que se cambiaron sus configuraciones de resumen</strong>',
	'LOG_CONFIG_DIGESTS_REGULAR_CRON_RUN'					=> '<strong>Ejecución cron regular (phpBB) de la aplicación de correo invocada</strong>',
	'LOG_CONFIG_DIGESTS_RESET_CRON_RUN_TIME'				=> '<strong>El tiempo de envío de los resúmenes fue restablecido.</strong>',
	'LOG_CONFIG_DIGESTS_RUN_TOO_SOON'						=> '<strong>Ha transcurrido menos de una hora desde que se ejecutaron los resúmenes por última vez. Abortado.</strong>',
	'LOG_CONFIG_DIGESTS_SIMULATION_DATE_TIME'				=> '<strong>El administrador eligió crear resúmenes para %1$s en %2$02d:00 tiempo del foro.</strong>',
	'LOG_CONFIG_DIGESTS_SORT_BY_ERROR'						=> '<strong>Se enviaron mensajes a las publicaciones de correo con un user_digest_sortby no válido = %1$s para %2$s</strong>',
	'LOG_CONFIG_DIGESTS_SYSTEM_CRON_RUN'					=> '<strong>Sistema cron ejecutado del remitente invocado.</strong>',
	'LOG_CONFIG_DIGESTS_TEST'								=> '<strong>%s</strong>',	// Se utiliza para la solución de problemas generales, por favor, mantenlo como está en todas las traducciones.
	'LOG_CONFIG_DIGESTS_TIMEZONE_ERROR'						=> '<strong>El user_timezone "%1$s" para el nombre de usuario "%2$s" no es válido. Supuso una zona horaria de "%3$s". Pídele al usuario que establezca una zona horaria adecuada en el Panel de control del usuario. Consulte http://php.net/manual/en/timezones.php para obtener una lista de zonas horarias válidas.</strong>',
	'LOG_CONFIG_DIGESTS_USER_DEFAULTS'						=> '<strong>Configuraciones predeterminadas modificadas del usuario del resumen</strong>',
));