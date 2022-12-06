INSERT INTO lang_definitions (`cons_id`, `lang_id`, `definition`)
VALUES (
		(
			SELECT lc.cons_id
			FROM lang_constants AS lc
			WHERE constant_name = 'Recurring Appointments'
				AND lc.cons_id NOT IN (
					SELECT DISTINCT cons_id
					FROM lang_definitions ld
					WHERE ld.lang_id = 4
				)
			LIMIT 1
		), 4, 'Citas Recurrentes'
	);
INSERT INTO lang_definitions (`cons_id`, `lang_id`, `definition`)
VALUES (
		(
			SELECT lc.cons_id
			FROM lang_constants AS lc
			WHERE constant_name = 'No Recurring Appointments'
				AND lc.cons_id NOT IN (
					SELECT DISTINCT cons_id
					FROM lang_definitions ld
					WHERE ld.lang_id = 4
				)
			LIMIT 1
		), 4, 'Sin Citas Recurrentes'
	);
INSERT INTO lang_definitions (`cons_id`, `lang_id`, `definition`)
VALUES (
		(
			SELECT lc.cons_id
			FROM lang_constants AS lc
			WHERE constant_name = 'Recurring Appointment'
				AND lc.cons_id NOT IN (
					SELECT DISTINCT cons_id
					FROM lang_definitions ld
					WHERE ld.lang_id = 4
				)
			LIMIT 1
		), 4, 'Cita Recurrente'
	);
INSERT INTO lang_definitions (`cons_id`, `lang_id`, `definition`)
VALUES (
		(
			SELECT lc.cons_id
			FROM lang_constants AS lc
			WHERE constant_name = 'Lab Results'
				AND lc.cons_id NOT IN (
					SELECT DISTINCT cons_id
					FROM lang_definitions ld
					WHERE ld.lang_id = 4
				)
			LIMIT 1
		), 4, 'Resultados de Laboratorio'
	);
INSERT INTO lang_constants (constant_name)
VALUES (' years old');
INSERT INTO lang_definitions (`cons_id`, `lang_id`, `definition`)
VALUES (
		(
			SELECT lc.cons_id
			FROM lang_constants AS lc
			WHERE constant_name = ' years old'
				AND lc.cons_id NOT IN (
					SELECT DISTINCT cons_id
					FROM lang_definitions ld
					WHERE ld.lang_id = 4
				)
			LIMIT 1
		), 4, ' años de edad'
	);
-- corregir traducciones mal
UPDATE lang_definitions
SET lang_id = 4
WHERE definition LIKE '%cita%'
	AND lang_id = 1;
-- corregir recurrente
UPDATE lang_definitions
SET lang_id = 4
WHERE definition LIKE '%recurrente%'
	AND lang_id = 1;