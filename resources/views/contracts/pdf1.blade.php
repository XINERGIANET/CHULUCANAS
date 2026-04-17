<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Préstamo Personal</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 0;
            padding: 25px 35px;
        }

        .titulo {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
        }

        .empresa {
            font-weight: bold;
        }

        .seccion-titulo {
            font-weight: bold;
            font-size: 9pt;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        p {
            margin-bottom: 5px;
            text-align: justify;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 9pt;
        }

        .tabla th {
            font-weight: bold;
            text-align: center;
        }

        .tabla td {
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        p {
            margin: 0 0 5px 0;
            text-align: justify;
        }
    </style>
</head>

<body>
    {{-- PÁGINA 1: CONTRATO --}}
    <div class="titulo">CONTRATO DE PRÉSTAMO PERSONAL</div>

    <p>Conste por el presente documento el contrato de mutuo que celebran de una parte:</p>

    <p><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> identificado con RUC N° 20611409355 inscrita en la
        partida electrónica Nº 11270956 del Registro de Personas Jurídicas de la Zona Registral de Piura; y de la otra
        parte;</p>

    <p style="margin-top: 10px;">
        @if ($contract->client_type == 'Personal')
            {{ $contract->name ?? '____________________________________' }} con DNI N°
            {{ $contract->document ?? '_________' }}, con domicilio en {{ $contract->address ?? '__________' }} distrito
            de {{ $contract->district->name ?? '_________________' }}, Provincia de {{ $contract->district->province->name ?? '__________________' }}, Departamento de {{ $contract->district->province->department->name ?? '_________________' }}, en su condición de EL
            MUTUATARIO / CLIENTE.

        @else
            ____________________________________ con DNI N° _________, con domicilio en __________ distrito de
            ________________,
            Provincia de ____________________, Departamento de Piura, en su condición de EL MUTUATARIO / CLIENTE.
        @endif
    </p>

    <div class="seccion-titulo">ANTECEDENTES:</div>

    <p><strong>PRIMERA.-</strong> EL MUTUATARIO/ CLIENTE, reconoce con carácter de declaración jurada que los datos
        generales contenidos en el presente contrato son ciertos; Asimismo, se compromete a mantener actualizada dicha
        información.</p>

    <p><strong>SEGUNDA.-</strong> <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> es una persona jurídica,
        que se dedica a la prestación de otras actividades de servicios financieros; en virtud al presente contrato a
        solicitud del EL MUTUATARIO / CLIENTE se proporciona préstamo en efectivo previa evaluación crediticia efectuada
        por el MUTUANTE.</p>

    <p>EL MUTUATARIO / CLIENTE para continuar con el ejercicio de su actividad económica, requiere contar con un mutuo
        dinerario, por la suma de S/ {{ $amount }} ({{ $requestedInWords }}), monto que es de libre disposición del EL MUTUATARIO / CLIENTE.</p>

    <p>EL MUTUATARIO / CLIENTE, se obliga a devolver a <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> la
        suma de dinero estipulada en el párrafo anterior, de acuerdo al cronograma de pagos y condiciones pactadas en el
        presente contrato.</p>

    <div class="seccion-titulo">OBLIGACIONES DE LAS PARTES:</div>

    <p><strong>TERCERA.-</strong> <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> se obliga a entregar la
        suma de dinero objeto de la prestación a su cargo al momento de la firma del presente documento, sin más
        constancia que las firmas de las partes plasmada en el comprobante de entrega en el anexo 1 - A del presente
        contrato, detallando el monto entregado a EL MUTUATARIO/CLIENTE.</p>

    <p><strong>CUARTA.-</strong> EL MUTUATARIO/ CLIENTE se obligan a devolver el íntegro del dinero objeto de préstamo,
        en un plazo de {{ $contract->quotas_number ?? '60' }} semanas como máximo, a partir de la firma del
        presente contrato, el pago se efectuara de manera semanal.</p>

    <p>Las partes acuerdan que el presente contrato de mutuo se celebra a titulo oneroso, en consecuencia el mutuatario
        se obliga al pago de un interés de 15% mensual a favor de <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span>, monto que será fraccionado y pagado en cada cuota de pago.</p>

    <p>Asimismo, se estipula que los intereses pactados no podrá ser modificados unilateralmente por <span
            class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>, salvo que ello implique condiciones más favorables
        para EL MUTUATARIO/ CLIENTE.</p>

    <p>Por tanto, EL MUTUATARIO/ CLIENTE devolverá la suma de S/
        {{ number_format($contract->payable_amount ?? 0, 2) }} (Prestamos + interés) como consecuencia del cumplimiento de la obligación
        pactada en presente contrato.</p>

    <p><strong>QUINTA.-</strong> EL MUTUATARIO/ CLIENTE, se obliga a devolver la suma pactada conforme al cronograma de
        pagos, detallado en el anexo 1 B que forma parte integra del presente contrato.</p>

    <p>EL MUTUATARIO/ CLIENTE autoriza a la empresa <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>, a
        cobrar la obligación materia del presente contrato, en el domicilio de EL MUTUATARIO/ CLIENTE señalado en el
        encabezado del presente contrato.</p>

    <p>Las cuotas pactadas y detalladas conforme al Anexo 1-B del presente contrato, serán cobradas mediante personal
        debidamente acreditado por parte de la empresa <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>.</p>

    <p>En caso que EL MUTUATARIO/ CLIENTE efectúen pago mediante medios electrónicos (aplicativos Yape, Plin,
        transferencia bancaria, interbancaria) el abono será comunicado a <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span> a fin de corroborar y confirmar el abono efectuado, comunicando a EL MUTUATARIO/CLIENTE la
        conformidad de pago.</p>

    <p>Para la validez de todas las comunicaciones y notificaciones a las partes, en relación con la ejecución del
        presente contrato, las partes señalan sus respectivos domicilios los indicados en la introducción de este
        documento, de igual manera en el formulario de evaluación de crédito; El cambio de domicilio, surtirá efecto a
        partir de la fecha de comunicación a la otra parte, por cualquier medio escrito y la aceptación debe ser previa
        evaluación de <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>.</p>

    <p><strong>SEXTA.-</strong> EL MUTUATARIO/ CLIENTE se obliga a cumplir fielmente con el calendario de pagos señalado
        en la cláusula anterior. En caso de incumplimiento de pago de cualquiera de las armadas, quedarán vencidas todas
        las demás, en consecuencia <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> estará autorizado a
        exigir el pago íntegro de la suma de dinero adeudada, más los intereses que se generen, quedando facultado para
        iniciar acciones pre judiciales y judiciales de cobranza con la finalidad de recuperar el crédito otorgado.</p>

    <p><strong>SÉTIMA.-</strong> En respaldo de las obligaciones asumidas, frente a <span class="empresa">APLICANDO
            CONFIANZA PERU S.A.C</span>, EL MUTUATARIO/ CLIENTE, suscribe pagare N° {{ $contract->number_pagare ?? '______________' }} emitido en
        forma incompleta. Los importes que no sean pagados por EL MUTUATARIO/ CLIENTE, en las oportunidades pactadas
        devengaran por todo el tiempo que demore el cumplimiento de la obligación, más gastos judiciales que genere la
        recuperación del crédito.</p>

    <p><strong>OCTAVA.-</strong> En lo no previsto por las partes en el presente contrato, ambas se someten a lo
        establecido por las normas del Código Civil y demás del sistema jurídico que resulten aplicables.</p>

    <p>Para efectos de cualquier controversia que se genere con motivo de la celebración y ejecución de este contrato,
        las partes se someten a la competencia territorial de los jueces y tribunales de la provincia del PIURA,</p>

    <p style="margin-top: 10px;">En señal de conformidad las partes suscriben este documento en la ciudad de Piura, el
        día {{ $contract->date ? $contract->date->format('d') : '________' }} de {{ $contract->date ? $contract->date->locale('es')->isoFormat('MMMM') : '________' }} del
        {{ $contract->date ? $contract->date->format('Y') : '________' }}.</p>

    <div style="margin-top: 80px;">
        <div style="border-top: 2px solid #000; width: 300px; padding-top: 5px;">
        </div>
        <p><strong><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></strong><br>
            RUC N° 20611409355</p>

        <p>Representante legal: _______________<br>
            DNI N°:__________________<br>
            Poderes inscritos: Partida Electrónica N° 11270956, del Registro de Personas Jurídicas de la Zona Registral
            N° I – Sede Piura / Oficina Registral Piura.<br>
            Domicilio:</p>
    </div>

    <div style="margin-top: 70px;">
        <div style="border-top: 2px solid #000; width: 300px; padding-top: 5px;">
        </div>
        <p><strong>EL MUTUATARIO/ CLIENTES</strong><br>
            NOMBRE: {{ $contract->client_type == 'Personal' ? $contract->name ?? '' : '' }}<br>
            DNI N°: {{ $contract->client_type == 'Personal' ? $contract->document ?? '' : '' }}</p>
    </div>

    <div class="page-break"></div>

    {{-- PÁGINA 2: ANEXO 1-A --}}
    <div class="titulo">ANEXO 1 – A. –COMPROBANTE DE ENTREGA.</div>

    <p style="margin-top: 20px;">
        <strong>RECIBO N°</strong> : {{ $contract->number_pagare ?? '______________' }}
    </p>
    <p>
        <strong>RECIBI DE</strong> : <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>
    </p>
    <p>
        <strong>CANTIDAD DE</strong> : {{ $amountInWords }}
    </p>
    <p>
        <strong>POR CONCEPTO DE:</strong> PRESTAMO DE DINERO PARA DEVOLVER – CAPITAL DE TRABAJO.
    </p>

    <p style="margin-top: 15px;">
        <strong>CLIENTE</strong> : {{ $contract->client_type == 'Personal' ? $contract->name ?? '' : '' }}
    </p>
    <p>
        <strong>DNI N°</strong> : {{ $contract->client_type == 'Personal' ? $contract->document ?? '' : '' }}
    </p>
    <p>
        <strong>RUTA / ASESOR</strong>
    </p>

    <table class="tabla" style="margin-top: 15px;">
        <thead>
            <tr>
                <th>NUMERO DE PRÉSTAMO</th>
                <th>NOMBRES Y APELLIDOS DEL CLIENTE</th>
                <th>NUMERO DE DNI</th>
                <th>DIRECCIÓN</th>
                <th>MONTO</th>
                <th>FIRMA.</th>
            </tr>
        </thead>
        <tbody>
            <tr style="height: 50px;">
                <td>{{ $contract->number_pagare ?? '______________' }}</td>
                <td>{{ $contract->client_type == 'Personal' ? $contract->name ?? '' : '' }}</td>
                <td>{{ $contract->client_type == 'Personal' ? $contract->document ?? '' : '' }}</td>
                <td>{{ $contract->client_type == 'Personal' ? $contract->address ?? '' : '' }}</td>
                <td>S/ {{ number_format($contract->requested_amount ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 150px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="border-top: 2px solid #000; width: 200px; margin: 0 auto; padding-top: 10px;">
                        <p style="margin: 0;"><strong>EL MUTUATARIO/ CLIENTES</strong></p>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="border-top: 2px solid #000; width: 250px; margin: 0 auto; padding-top: 10px;">
                        <p style="margin: 0;"><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- PÁGINA 3: ANEXO 1-B --}}
    <div class="titulo">ANEXO 1-B</div>
    <div class="titulo" style="font-size: 11pt; margin-top: 5px;">CRONOGRAMA DE PAGOS.</div>

    <p style="margin-top: 20px;">EL MUTUATARIO/ CLIENTE, bajo el presente documento deja constancia que toma
        conocimiento del cronograma de pagos que se indica el contrato de MUTUO DINERARIO.</p>

    <p style="margin-top: 15px;"><strong>Datos del Prestamo:</strong></p>

    <p>Monto: S/.{{ number_format($contract->requested_amount ?? 0, 2) }}
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Tasa: 15% mensual &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Cuotas:
        {{ $contract->quotas_number ?? '60' }}<br>
        Monto a Devolver: S/. {{ number_format($contract->payable_amount ?? 0, 2) }}<br>
        Periodicidad: SEMANAL</p>

    <p style="margin-top: 15px;"><strong>CRONOGRAMA</strong></p>
    <table class="tabla" style="margin-top: 10px;">
        <thead>
            <tr>
                <th style="text-align: center;">CUOTA</th>
                <th style="text-align: center;">FECHA</th>
                <th style="text-align: center;">TOTAL A PAGAR</th>
            </tr>
        </thead>
        <tbody>
            @if ($contract->quotas && count($contract->quotas) > 0)
                @foreach ($contract->quotas as $quota)
                    <tr>
                        <td style="text-align: center;">{{ $quota->number }}</td>
                        <td style="text-align: center;">{{ $quota->date ? $quota->date->format('d/m/Y') : '' }}</td>
                        <td style="text-align: center;">S/. {{ number_format($quota->amount ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            @else
                @for ($i = 1; $i <= ($contract->quotas_number ?? 4); $i++)
                    <tr>
                        <td style="text-align: center;">{{ $i }}</td>
                        <td style="text-align: center;">____/____/____</td>
                        <td style="text-align: center;">S/. _______</td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    <div style="margin-top: 150px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="border-top: 2px solid #000; width: 200px; margin: 0 auto; padding-top: 10px;">
                        <p style="margin: 0;"><strong>EL MUTUATARIO/ CLIENTES</strong></p>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 0;">
                    <div style="border-top: 2px solid #000; width: 250px; margin: 0 auto; padding-top: 10px;">
                        <p style="margin: 0;"><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- PÁGINA 4: PAGARÉ --}}
    <div class="titulo">PAGARE</div>

    <p style="margin-top: 20px;">
        <strong>PAGARE N°</strong> : {{ $contract->number_pagare ?? '______________' }}<br>
        <strong>FECHA DE EMISIÓN:</strong>
        {{ $contract->date ? $contract->date->format('d/m/Y') : '____________________' }} &nbsp;&nbsp;&nbsp;
        <strong>IMPORTE DEUDOR</strong> S/ {{ number_format($contract->payable_amount ?? 0, 2) }}<br>
        <strong>FECHA DE VENCIMIENTO:</strong> 
    </p>

    <p>Pagaré, a la orden de la empresa <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span>, con RUC N° 20611409355, en la fecha de vencimiento indicada, la suma de S/
        {{ number_format($contract->payable_amount ?? 0, 2) }} soles, importe que corresponde a la liquidación de la suma que adeudo, en virtud al contrato de
        crédito, sin lugar a reclamo de alguna clase, para cuyo fiel y exacto cumplimiento.</p>

    <p style="margin-top: 15px;"><strong>CLAUSULAS ESPECIALES.</strong></p>

    <p><strong>1.</strong> Este pagaré debe ser pagado en la misma moneda que expresa el titulo valor.</p>

    <p><strong>2.</strong> A su vencimiento, podrá de ser prorrogado por <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span>, o por su tenedor por el plazo que este señale en el mismo documento, sin que sea necesario
        intervención alguna del obligado principal.</p>

    <p><strong>3.</strong> El importe de este Pagaré, y/o de las cuotas del crédito que representa, generará desde la
        fecha de emisión hasta la fecha de su respectivo (s) vencimiento(s), un interés compensatorio que se pacta en la
        tasa de 15% mensual.</p>

    <p><strong>4.</strong> El importe deudor se les aplicará los intereses compensatorios e intereses moratorios a las
        tasas máximas autorizadas a <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span>, o permitidas a su
        ultimo Tenedor.</p>

    <p>En caso de no ser cancelado el importe de una o más cuotas del crédito que representa este Pagaré, los constituye
        en mora aplicándose los intereses moratorios desde la fecha de vencimiento hasta su total cancelación, sin que
        sea necesario requerimiento alguno de pago para constituir mora al obligado principal, incurriéndose en ésta
        automáticamente por el sólo hecho del vencimiento</p>

    <p>El obligado principal, acepta igualmente que las tasas de interés compensatorio y/o moratorio puedan ser variadas
        por la empresa <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> y /o su ultimo tenedor sin necesidad
        de aviso previo, de acuerdo a las tasas que ésta tenga vigente.</p>

    <p><strong>5.</strong> De conformidad con el Articulo 52 de la ley N° 27287, queda expresamente establecido que el
        presente pagare, no requiere ser protestado; Sin embargo, <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span>, queda facultado, a protestarlo por falta de pago, si así lo estime conveniente, asumiendo el
        costo de tales protestos, pudiendo trasladar dicho costo al obligado principal.</p>

    <p><strong>6.</strong> La empresa <span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span> y/o tenedor podrá
        entablar acción judicial para efectuar el cobro de este Pagaré donde lo tuviera conveniente, para todos los
        efectos y consecuencias que pudieran derivarse de la emisión del presente Pagaré, el indicado en este documento,
        lugar donde se enviaran los avisos y se harán llegar todas las comunicaciones y/o notificaciones judiciales que
        resulten necesarias. El presente Pagaré está sujeto a la Ley Peruana de Títulos Valores vigente a la fecha de
        suscripción de Pagaré.</p>

    <div style="margin-top: 40px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <table style="border-collapse: collapse; margin-bottom: 8px;">
                        <tr>
                            <td style="vertical-align: bottom; border: none; padding: 0;">
                                <div style="border-top: 2px solid #000; width: 150px; height: 1px;"></div>
                            </td>
                            <td style="vertical-align: bottom; border: none; padding: 0 0 0 10px;">
                                <div style="border: 1px solid #000; width: 80px; height: 80px;"></div>
                            </td>
                        </tr>
                    </table>
                    <p style="margin: 2px 0; font-weight: bold; font-size: 9pt;">FIRMA</p>
                    <p style="font-size: 8pt; text-align: left; margin: 2px 0; line-height: 1.4;">
                        Nombre:………………………………………<br>
                        DNI N°: ……………………………………<br>
                        Estado Civil………………………………<br>
                        Domicilio:…………………………………
                    </p>
                </td>
                <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                    <table style="border-collapse: collapse; margin-bottom: 8px;">
                        <tr>
                            <td style="vertical-align: bottom; border: none; padding: 0;">
                                <div style="border-top: 2px solid #000; width: 150px; height: 1px;"></div>
                            </td>
                            <td style="vertical-align: bottom; border: none; padding: 0 0 0 10px;">
                                <div style="border: 1px solid #000; width: 80px; height: 80px;"></div>
                            </td>
                        </tr>
                    </table>
                    <p style="margin: 2px 0; font-weight: bold; font-size: 9pt;">FIRMA</p>
                    <p style="font-size: 8pt; text-align: left; margin: 2px 0; line-height: 1.4;">
                        Nombre:………………………………………<br>
                        DNI N°:……………………………………<br>
                        Estado Civil:……………………………<br>
                        Domicilio: ………………………………
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
