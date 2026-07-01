-- ============================================================
-- FIX: Trigger validar_pago_inscripcion para plan CRÉDITO
--
-- Cambios:
--   1. Reintentos de materia reprobada SIEMPRE pagan (antes usaban un slot libre)
--   2. Los slots libres se cuentan por materias DISTINTAS, no por total de filas
-- ============================================================

CREATE OR REPLACE FUNCTION validar_pago_inscripcion()
RETURNS TRIGGER AS $$
DECLARE
    materia_actual      INT;
    carrera_estudiante  INT;
    intentos_previos    INT;
    intentos_credito    INT;
    pago_carrera_id     INT;
    costo_materia       DECIMAL(10,2);
    costo_total_carrera DECIMAL(10,2);
    total_materias      INT;
    forma_pago_actual   VARCHAR(10);
    consumos_actuales   INT;
    mat_cubiertas       INT;
BEGIN
    SELECT oa.id_materia INTO materia_actual
    FROM grupos oa WHERE oa.id_oferta = NEW.id_oferta;

    SELECT e.id_carrera_actual INTO carrera_estudiante
    FROM estudiantes e WHERE e.id_estudiante = NEW.id_estudiante;

    IF carrera_estudiante IS NOT NULL THEN

        SELECT pcc.id_pago_carrera, pcc.forma_pago
        INTO pago_carrera_id, forma_pago_actual
        FROM pago_carrera_completa pcc
        WHERE pcc.id_estudiante = NEW.id_estudiante
          AND pcc.id_carrera = carrera_estudiante
        LIMIT 1;

        IF pago_carrera_id IS NOT NULL THEN

            -- -------------------------------------------------------
            -- CREDITO: primeras N materias DISTINTAS gratis, reintentos siempre pagan
            -- -------------------------------------------------------
            IF forma_pago_actual = 'credito' THEN

                SELECT materias_cubiertas INTO mat_cubiertas
                FROM pago_carrera_completa
                WHERE id_pago_carrera = pago_carrera_id;

                -- ¿Ya intentó esta materia antes? (aprobó o reprobó)
                SELECT COUNT(*) INTO intentos_credito
                FROM consumo_materias_carrera
                WHERE id_pago_carrera = pago_carrera_id
                  AND id_materia = materia_actual;

                IF intentos_credito > 0 THEN
                    -- REINTENTO (reprobó y vuelve a inscribirse): siempre cobra
                    SELECT monto INTO costo_materia
                    FROM cuotas_carrera
                    WHERE id_pago_carrera = pago_carrera_id AND estado = 'pendiente'
                    ORDER BY numero_cuota ASC LIMIT 1;

                    IF costo_materia IS NOT NULL THEN
                        INSERT INTO pago_materia_suelta
                            (id_inscripcion, monto_pagado, fecha_pago, estado)
                        VALUES (NEW.id_inscripcion, costo_materia, CURRENT_DATE, 'pendiente');

                        INSERT INTO consumo_materias_carrera
                            (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                        VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, intentos_credito + 1, TRUE);
                    ELSE
                        -- Carrera saldada → reintento gratis
                        INSERT INTO consumo_materias_carrera
                            (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                        VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, intentos_credito + 1, FALSE);
                    END IF;

                ELSE
                    -- NUEVA materia: verificar slots libres (contar materias DISTINTAS ya usadas)
                    SELECT COUNT(DISTINCT id_materia) INTO consumos_actuales
                    FROM consumo_materias_carrera
                    WHERE id_pago_carrera = pago_carrera_id;

                    IF consumos_actuales < mat_cubiertas THEN
                        -- GRATIS: cubierta por el adelanto
                        INSERT INTO consumo_materias_carrera
                            (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                        VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, 1, FALSE);

                    ELSE
                        -- Slots agotados: buscar proxima cuota pendiente
                        SELECT monto INTO costo_materia
                        FROM cuotas_carrera
                        WHERE id_pago_carrera = pago_carrera_id AND estado = 'pendiente'
                        ORDER BY numero_cuota ASC LIMIT 1;

                        IF costo_materia IS NOT NULL THEN
                            INSERT INTO pago_materia_suelta
                                (id_inscripcion, monto_pagado, fecha_pago, estado)
                            VALUES (NEW.id_inscripcion, costo_materia, CURRENT_DATE, 'pendiente');

                            INSERT INTO consumo_materias_carrera
                                (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                            VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, 1, TRUE);
                        ELSE
                            -- Sin cuotas pendientes: carrera saldada → gratis
                            INSERT INTO consumo_materias_carrera
                                (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                            VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, 1, FALSE);
                        END IF;
                    END IF;
                END IF;

            -- -------------------------------------------------------
            -- CONTADO: primera vez gratis, reintento paga
            -- -------------------------------------------------------
            ELSE
                SELECT COUNT(*) INTO intentos_previos
                FROM consumo_materias_carrera cmc
                WHERE cmc.id_pago_carrera = pago_carrera_id
                  AND cmc.id_materia = materia_actual;

                IF intentos_previos = 0 THEN
                    -- Primera vez: GRATIS
                    INSERT INTO consumo_materias_carrera
                        (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                    VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, 1, FALSE);
                ELSE
                    -- Reintento (reprobo): pago adicional
                    SELECT c.costo_carrera_completa, COUNT(mc.id_malla)
                    INTO costo_total_carrera, total_materias
                    FROM carreras c
                    LEFT JOIN malla_curricular mc ON mc.id_carrera = c.id_carrera
                    WHERE c.id_carrera = carrera_estudiante
                    GROUP BY c.costo_carrera_completa;

                    IF costo_total_carrera IS NOT NULL AND total_materias > 0 THEN
                        costo_materia := ROUND(costo_total_carrera / total_materias, 2);
                    ELSE
                        SELECT costo_mensual INTO costo_materia FROM materias WHERE id_materia = materia_actual;
                    END IF;

                    INSERT INTO pago_materia_suelta
                        (id_inscripcion, monto_pagado, fecha_pago, estado)
                    VALUES (NEW.id_inscripcion, costo_materia, CURRENT_DATE, 'pendiente');

                    INSERT INTO consumo_materias_carrera
                        (id_pago_carrera, id_materia, id_inscripcion, intento_numero, pagado_adicional)
                    VALUES (pago_carrera_id, materia_actual, NEW.id_inscripcion, intentos_previos + 1, TRUE);
                END IF;
            END IF;

        ELSE
            -- -------------------------------------------------------
            -- SIN PAGO DE CARRERA: pago por materia suelta
            -- -------------------------------------------------------
            SELECT c.costo_carrera_completa, COUNT(mc.id_malla)
            INTO costo_total_carrera, total_materias
            FROM carreras c
            LEFT JOIN malla_curricular mc ON mc.id_carrera = c.id_carrera
            WHERE c.id_carrera = carrera_estudiante
            GROUP BY c.costo_carrera_completa;

            IF costo_total_carrera IS NOT NULL AND total_materias > 0 THEN
                costo_materia := ROUND(costo_total_carrera / total_materias, 2);
            ELSE
                SELECT costo_mensual INTO costo_materia FROM materias WHERE id_materia = materia_actual;
            END IF;

            INSERT INTO pago_materia_suelta
                (id_inscripcion, monto_pagado, fecha_pago, estado)
            VALUES (NEW.id_inscripcion, costo_materia, CURRENT_DATE, 'pendiente');
        END IF;

    ELSE
        -- Sin carrera asignada: usa costo_mensual de la materia
        SELECT costo_mensual INTO costo_materia FROM materias WHERE id_materia = materia_actual;
        INSERT INTO pago_materia_suelta
            (id_inscripcion, monto_pagado, fecha_pago, estado)
        VALUES (NEW.id_inscripcion, costo_materia, CURRENT_DATE, 'pendiente');
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
