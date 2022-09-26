-- Issue #1: Modificar Identificación de RRHH 
--
-- Modificar Leyendas:
--
-- Nombre -> Primer nombre /
-- Identificación de impuesto federal-> Tipo de identificación profesional
-- Nro. licencia estatal -> Número de identificación profesoional
-- Provider type-> Tipo de profesional
-- Control de acceso-> Perfiles de acceso
-- 
-- Cambiar nompre por Nombre -> Primer nombre
UPDATE lang_definitions AS ld,
lang_constants AS lc 
SET definition = 'Primer nombre' 
WHERE
	ld.cons_id = lc.cons_id 
	AND lang_id = 4 
	AND constant_name = 'First Name';
UPDATE lang_definitions AS ld,
lang_constants AS lc 
SET definition = 'Tipo de identificación profesional' 
WHERE
	ld.cons_id = lc.cons_id 
	AND lang_id = 4 
	AND definition = 'Identificación de impuesto federal';--
-- Nro. licencia estatal -> Número de identificación profesoional
--
UPDATE lang_definitions AS ld,
lang_constants AS lc 
SET definition = 'Número de identificación profesoional' 
WHERE
	ld.cons_id = lc.cons_id 
	AND lang_id = 4 
	AND definition = 'Nro. licencia estatal';-- 
-- Control de acceso-> Perfiles de acceso
--
UPDATE lang_definitions AS ld,
lang_constants AS lc 
SET definition = 'Perfiles de acceso' 
WHERE
	ld.cons_id = lc.cons_id 
	AND lang_id = 4 
	AND definition = 'Control de acceso';-- Provider type-> Tipo de profesional
-- no esta en la carga inicial
--
INSERT INTO `openemr`.`lang_definitions` ( `cons_id`, `lang_id`, `definition` )
VALUES
	(
		(
		SELECT
			lc.cons_id 
		FROM
			`openemr`.`lang_constants` AS lc 
		WHERE
			`constant_name` = 'Provider Type' 
			AND lc.cons_id NOT IN ( SELECT DISTINCT cons_id FROM lang_definitions ld WHERE ld.lang_id = 4 ) 
			LIMIT 1 
		),
		4,
	'Tipo de profesional' 
	);