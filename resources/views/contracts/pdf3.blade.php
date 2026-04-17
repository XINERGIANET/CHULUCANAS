<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Préstamo Grupal</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
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
            font-size: 8.5pt;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        p {
            margin: 0 0 5px 0;
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
            padding: 6px;
            font-size: 8pt;
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
    </style>
</head>

<body>
    <h1>CONTRATO DE PRÉSTAMO GRUPAL</h1>
    <div class="content">
        <p>Conste por el presente documento el contrato de mutuo que celebran de una parte:</p>
        <p><strong>{{ $company }}</strong> identificado con RUC N° {{ $ruc }} inscrita en la partida
            electrónica Nº 11270956 del Registro de Personas Jurídicas de la Zona Registral de Piura; y de la otra
            parte:</p>

        <h3>INTEGRANTES DEL GRUPO</h3>
        <table class="tabla" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nombre</th>
                    <th>DNI</th>
                    <th>Dirección</th>
                </tr>
            </thead>
            <tbody>
                @if ($people)
                    @foreach ($people as $i => $p)
                        <tr style="height: 50px;">
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->document }}</td>
                            <td>{{ $p->address }}</td>
                        </tr>
                    @endforeach
                @else
                    @for ($i = 1; $i <= 4; $i++)
                        <tr style="height: 50px;">
                            <td>{{ $i }}</td>
                            <td>Nombre Ejemplo</td>
                            <td>12345678</td>
                            <td>Dirección Ejemplo</td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>

        @if ($people && count($people) > 0)
            @php
                $firstAddress = $people[0]->address;
            @endphp
        @else
            @php
                $firstAddress = 'Dirección Ejemplo';
            @endphp
        @endif
        <p>Para efectos del presente contrato, indican domicilio en común en {{ $firstAddress }}, distrito
            de {{ $contract->district->name ?? '_________________' }}, Provincia de
            {{ $contract->district->province->name ?? '__________________' }}, Departamento de
            {{ $contract->district->province->department->name ?? '_________________' }}, en su condición de EL
            MUTUATARIO / CLIENTES,
            siendo responsables / fiadores solidarios en el presente contrato.</p>

        <h3 class="section-title">ANTECEDENTES:</h3>
        <p><strong>PRIMERA.-</strong> EL MUTUATARIO/ CLIENTES, reconoce con carácter de declaración jurada que los datos
            generales contenidos en el presente contrato son ciertos; asimismo, se compromete a mantener actualizada
            dicha información.</p>
        <p><strong>SEGUNDA.- {{ $company }}</strong> es una persona jurídica que se dedica a la prestación de
            otras
            actividades de servicios financieros; en virtud al presente contrato a solicitud del MUTUATARIO / CLIENTES
            se proporciona préstamo en efectivo previa evaluación crediticia efectuada por el MUTUANTE.</p>
        <p>EL MUTUATARIO / CLIENTES para continuar con el ejercicio de su actividad económica, requiere contar con un
            mutuo dinerario, por la suma de S/ {{ $amount }} ({{ $requestedInWords }}), monto que es de libre
            disposición del EL MUTUATARIO / CLIENTE.</p>
        <p>EL MUTUATARIO / CLIENTES, se obliga a devolver a <strong>{{ $company }}</strong> la suma de dinero
            estipulada en el párrafo anterior, de acuerdo con el cronograma de pagos y condiciones pactadas en el
            presente contrato.</p>

        <h3 class="section-title">OBLIGACIONES DE LAS PARTES:</h3>
        <p><strong>TERCERA.- {{ $company }}</strong> se obliga a entregar la suma de dinero objeto de la
            prestación a su cargo al momento de la firma del presente documento, sin más constancia que las firmas de
            las partes plasmada en el comprobante de entrega en el anexo 1 - A del presente contrato, detallando el
            monto entregado a cada MUTUATARIO/CLIENTES.</p>
        <p><strong>CUARTA.-</strong> EL MUTUATARIO/ CLIENTES se obligan a devolver el íntegro del dinero objeto de
            préstamo, en un plazo de {{ $numberWeeks }} semanas como máximo, a partir de la firma del presente
            contrato, el pago se efectuara de manera semanal</p>
        <p>Las partes acuerdan que el presente contrato de mutuo se celebra a título oneroso, en consecuencia el
            mutuatario se obliga al pago de un interés de 15% mensual a favor de <strong>{{ $company }}</strong>,
            monto que será fraccionado y pagado en cada cuota de pago.</p>
        <p>Asimismo, se estipula que los intereses pactados no podrán ser modificados unilateralmente por
            <strong>{{ $company }}</strong>, salvo que ello implique condiciones más favorables para EL
            MUTUATARIO/ CLIENTES.
        </p>
        <p>Por tanto, EL MUTUATARIO/ CLIENTES devolverá la suma de S/ {{ $payable_amount }} (Prestamos + interés) como
            consecuencia del cumplimiento de la obligación pactada en presente contrato.</p>
        <p><strong>QUINTA.-</strong> EL MUTUATARIO/ CLIENTES, de obliga a devolver la suma pactada conforme al
            cronograma de pagos, detallado en el anexo 1 B que forma parte integra del presente contrato.</p>
        <p>EL MUTUATARIO/ CLIENTES autorizan a la empresa <strong>{{ $company }}</strong>, a cobrar la obligación
            materia del presente contrato, en el domicilio de EL MUTUATARIO/ CLIENTES señalado en el encabezado del
            presente contrato.</p>
        <p>Las cuotas pactadas y detalladas conforme al Anexo 1-B del presente contrato, serán cobradas mediante
            personal debidamente acreditado por parte de la empresa <strong>{{ $company }}</strong></p>
        <p>En caso de que EL MUTUATARIO/ CLIENTES efectúen pago mediante medios electrónicos (aplicativos Yape, Plin,
            transferencia bancaria, interbancaria) el abono será comunicado a <strong>{{ $company }}</strong> a
            fin de corroborar y confirmar el abono efectuado, comunicando a EL MUTUATARIO/CLIENTES la conformidad de
            pago.</p>
        <p>Para la validez de todas las comunicaciones y notificaciones a las partes, en relación con la ejecución del
            presente contrato, las partes señalan sus respectivos domicilios los indicados en la introducción de este
            documento, de igual manera en el formulario de evaluación de crédito; El cambio de domicilio, surtirá efecto
            a partir de la fecha de comunicación a la otra parte, por cualquier medio escrito y la aceptación debe ser
            previa evaluación de <strong>{{ $company }}</strong></p>
        <p><strong>SEXTA.-</strong> EL MUTUATARIO/ CLIENTES se obliga a cumplir fielmente con el calendario de pagos
            señalado en la cláusula anterior. En caso de incumplimiento de pago de cualquiera de las armadas, quedarán
            vencidas todas las demás, en consecuencia, <strong>{{ $company }}</strong> estará autorizado a exigir
            el pago íntegro de la suma de dinero adeudada, más los intereses que se generen, quedando facultado para
            iniciar acciones prejudiciales y judiciales de cobranza con la finalidad de recuperar el crédito otorgado.
        </p>
        <p><strong>SÉTIMA.-</strong> En respaldo de las obligaciones asumidas, frente a
            <strong>{{ $company }}</strong>, EL MUTUATARIO/ CLIENTES, suscriben pagare N°
            {{ $contract->number_pagare ?? '_____________________' }} emitido en forma incompleta. Los importes que no
            sean
            pagados por EL MUTUATARIO/ CLIENTES, en las oportunidades pactadas devengaran por todo el tiempo que demore
            el cumplimiento de la obligación, más gastos judiciales que genere la recuperación del crédito.
        </p>
        <p><strong>OCTAVA.- <u>SOLIDARIA</u>:</strong> los fiadores que suscriben este documento se constituyen como
            fiadores solidarios de EL MUTUATARIO/ CLIENTES, sin beneficio de excusión, comprometiéndose a pagar las
            obligaciones asumidas por EL MUTUATARIO/ CLIENTES a favor de <strong>{{ $company }}</strong>;
            incluyendo los gastos de toda clase que se deriven de este contrato, sin reserva ni limitación alguna. los
            fiadores y EL MUTUATARIO/ CLIENTES aceptan desde ahora las prórrogas y renovaciones que puedan conceder
            frente a <strong>{{ $company }}</strong>, sin necesidad que les sean comunicadas ni suscritas por
            ellos. </p>
        <p>Asimismo, los fiadores renuncian a hacer uso de la facultad otorgada por el artículo 1899° del Código Civil;
            Asimismo, autorizan en este documento desde ahora y en forma irrevocable a
            <strong>{{ $company }}</strong> para que, si así lo decidiera, cobre el importe parcial o total de las
            obligaciones que se deriven de este contrato o en cualquier otra cuenta/deuda que mantengan o pudieran tener
            contra en el <strong>{{ $company }}</strong>, En caso dichos importes no sean pagados por EL
            MUTUATARIO/ CLIENTES, los fiadores renuncian a exigir al <strong>{{ $company }}</strong> la
            transferencia de las garantías otorgadas por los fiadores, en caso cumpla con pagar las obligaciones
            asumidas por EL MUTUATARIO/ CLIENTES en virtud del presente contrato, y gastos que se generen; así como
            cualquier otra obligación derivada del mismo (esta disposición es aplicable en tanto se mantengan
            obligaciones pendientes con <strong>{{ $company }}</strong>).
        </p>
        <p>La presente fianza se constituye en virtud al <strong>Pagare N°
                {{ $contract->number_pagare ?? '_____________________' }}.</strong></p>
        <p>Las partes convienen en que el presente contrato de mutuo se celebra a título oneroso, en consecuencia, EL
            MUTUATARIO/ CLIENTES está obligado al pago de intereses compensatorios en favor de
            <strong>{{ $company }}</strong> de acuerdo con la tasa y forma de pago a que se refiere la cláusula
            siguiente CUARTA, del presente contrato.
        </p>
        <p><strong>NOVENO.-</strong> En lo no previsto por las partes en el presente contrato, ambas se someten a lo
            establecido por las normas del Código Civil y demás del sistema jurídico que resulten aplicables.</p>
        <p>Para efectos de cualquier controversia que se genere con motivo de la celebración y ejecución de este
            contrato, las partes se someten a la competencia territorial de los jueces y tribunales de la provincia del
            PIURA, </p>
        <p>En señal de conformidad las partes suscriben este documento en la ciudad de Piura, el día
            {{ $contractDate }}.</p>
        <div style="margin-top: 60px;">
            <div style="border-top: 2px solid #000; width: 300px; padding-top: 5px;">
            </div>
            <p><strong><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></strong><br>
                RUC N° 20611409355</p>

            <p>Representante legal: _______________<br>
                DNI N°:__________________<br>
                Poderes inscritos: Partida Electrónica N° 11270956, del Registro de Personas Jurídicas de la Zona
                Registral
                N° I – Sede Piura / Oficina Registral Piura.<br>
                Domicilio:</p>
        </div>

        @foreach ($people as $i => $p)
            @if ($i % 2 == 0)
                <div style="margin-top: 40px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
            @endif
            <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
                <table style="border-collapse: collapse; margin-bottom: 8px;">
                    <tr>
                        <td style="vertical-align: bottom; border: none; padding: 0;">
                            <br>
                        </td>
                        <td style="vertical-align: bottom; border: none; padding: 0 0 0 10px;">

                        </td>
                    </tr>
                </table>
                <div style="border-top: 2px solid #000; width: 300px; margin: 0 auto; padding-top: 10px;">
                    <p><strong>EL MUTUATARIO/ CLIENTES</strong></p>
                    <p><strong>Nombre:</strong> {{ $p->name ?? 'Nombre Ejemplo' }}</p>
                    <p><strong>DNI N°:</strong> {{ $p->document ?? '12345678' }}</p>
                </div>
            </td>
            @if ($i % 2 == 1 || $i == count($people) - 1)
                </tr>
                </table>
    </div>
    @endif
    @endforeach

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
        <strong>CLIENTE</strong> : {{ $contract->group_name ?? '' }}
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
            @if ($people && count($people) > 0)
                @foreach ($people as $i => $p)
                    <tr style="height: 50px;">
                        @if ($i == 0)
                            <td rowspan="{{ count($people) }}">{{ $contract->number_pagare ?? '______________' }}</td>
                        @endif
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->document }}</td>
                        <td>{{ $p->address }}</td>
                        <td>{{ $p->quotes }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @else
                <tr style="height: 50px;">
                    <td>{{ $contract->number_pagare ?? '______________' }}</td>
                    <td>Nombre Ejemplo</td>
                    <td>12345678</td>
                    <td>Dirección Ejemplo</td>
                    <td>S/ {{ number_format($contract->requested_amount ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="margin: 80px auto 0 auto; text-align: center;">
        <div style="border-top: 2px solid #000; width: 250px; margin: 0 auto; padding-top: 5px;">
            <p><strong><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></strong></p>
        </div>
    </div>

    @foreach ($people as $i => $p)
        @if ($i % 2 == 0)
            <div style="margin-top: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
        @endif
        <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
            <table style="border-collapse: collapse; margin-bottom: 8px;">
                <tr>
                    <td style="vertical-align: bottom; border: none; padding: 0;">
                        <br>
                    </td>
                    <td style="vertical-align: bottom; border: none; padding: 0 0 0 10px;">

                    </td>
                </tr>
            </table>
            <div style="border-top: 2px solid #000; width: 300px; margin: 0 auto; padding-top: 10px;">
                <p><strong>EL MUTUATARIO/ CLIENTES</strong></p>
                <p><strong>Nombre:</strong> {{ $p->name ?? 'Nombre Ejemplo' }}</p>
                <p><strong>DNI N°:</strong> {{ $p->document ?? '12345678' }}</p>
            </div>
        </td>
        @if ($i % 2 == 1 || $i == count($people) - 1)
            </tr>
            </table>
            </div>
        @endif
    @endforeach

    <div class="page-break"></div>

    {{-- PÁGINA 3: ANEXO 1-B --}}
    <div class="titulo">ANEXO 1-B</div>
    <div class="titulo" style="font-size: 11pt; margin-top: 5px;">CRONOGRAMA DE PAGOS.</div>

    <p style="margin-top: 20px;">EL MUTUATARIO/ CLIENTE, bajo el presente documento deja constancia que toma
        conocimiento del cronograma de pagos que se indica el contrato de MUTUO DINERARIO.</p>

    <p style="margin-top: 10px;"><strong>Datos del Prestamo:</strong></p>

    <p>Monto: S/.{{ number_format($contract->requested_amount ?? 0, 2) }}
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Tasa: 15% mensual &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Cuotas:
        {{ $contract->quotas_number ?? '60' }}<br>
        Monto a Devolver: S/. {{ number_format($contract->payable_amount ?? 0, 2) }}<br>
        Periodicidad: SEMANAL</p>

    <p style="margin-top: 10px;"><strong>CRONOGRAMA</strong></p>
    <table class="tabla" style="margin-top: 10px;">
        <thead>
            <tr>
                <th style="text-align: center;">CUOTA</th>
                <th style="text-align: center;">FECHA</th>
                <th style="text-align: center;">TOTAL A PAGAR</th>
            </tr>
        </thead>
        <tbody>
            @php
                $hasQuotas = $contract->quotas && count($contract->quotas) > 0;
            @endphp

            @if ($hasQuotas)
                @php
                    // Agrupar por número de cuota
                    $weeklyGroups = $contract->quotas->groupBy('number');
                    // Calcular el monto por cuota: monto total a devolver / número de cuotas
                    $payableAmount = $contract->payable_amount ?? 0;
                    $quotasNumber = $contract->quotas_number ?? 1;
                    $amountPerQuota = $quotasNumber > 0 ? $payableAmount / $quotasNumber : 0;
                    // Redondear hacia arriba a 1 decimal (0.10)
                    $amountPerQuota = ceil($amountPerQuota * 10) / 10;
                @endphp

                @foreach ($weeklyGroups as $number => $group)
                    @php
                        $first = $group->first();
                        $date = $first->date ? $first->date->format('d/m/Y') : '';
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $number }}</td>
                        <td style="text-align: center;">{{ $date }}</td>
                        <td style="text-align: center;">S/. {{ number_format($amountPerQuota, 2) }}</td>
                    </tr>
                @endforeach
            @else
                @php
                    $payableAmount = $contract->payable_amount ?? 0;
                    $quotasNumber = $contract->quotas_number ?? 4;
                    $amountPerQuota = $quotasNumber > 0 ? $payableAmount / $quotasNumber : 0;
                    // Redondear hacia arriba a 1 decimal (0.10)
                    $amountPerQuota = ceil($amountPerQuota * 10) / 10;
                @endphp
                @for ($i = 1; $i <= $quotasNumber; $i++)
                    <tr>
                        <td style="text-align: center;">{{ $i }}</td>
                        <td style="text-align: center;">____/____/____</td>
                        <td style="text-align: center;">S/. {{ number_format($amountPerQuota, 2) }}</td>
                    </tr>
                @endfor
            @endif
        </tbody>
    </table>

    {{-- Si el contrato es grupal mostramos desglose por integrante: una fila por cuota (CUOTA | CLIENTE | DNI | MONTO | FIRMA) --}}
    @if ($contract->client_type == 'Grupo')
        <h4 style="margin-top: 12px;">Distribución por integrante</h4>
        @php
            $hasQuotas = $contract->quotas && count($contract->quotas) > 0;
            // Ordenar cuotas por número
            $sortedQuotas = $hasQuotas ? $contract->quotas->sortBy('number') : collect();
        @endphp

        <table class="tabla" style="margin-top: 8px; height: 120px;">
            <thead>
                <tr>
                    <th style="text-align: center;">CLIENTE</th>
                    <th style="text-align: center;">DNI</th>
                    <th style="text-align: center;">MONTO PEDIDO</th>
                    <th style="text-align: center;">CUOTA A PAGAR</th>
                    <th style="text-align: center;">FIRMA</th>
                </tr>
            </thead>
            <tbody>
                @if ($hasQuotas)
                    @php
                        // Agrupar cuotas por cliente (documento o nombre)
                        $groups = $contract->quotas->groupBy(function ($item) {
                            return $item->person_document ?? ($item->person_name ?? 'SIN_IDENTIFICAR');
                        });
                    @endphp

                    @php
                        $processedDocuments = [];
                    @endphp

                    @foreach ($groups as $key => $group)
                        @php
                            $first = $group->first();
                            $person_name = $first->person_name ?? 'N/A';
                            $person_document = $first->person_document ?? $key;
                            // Mostrar el monto de una sola cuota (no sumar): tomar la primera cuota disponible
                            $monto_a_pagar = $group->first()->amount ?? 0;

                            // Intentar obtener el monto pedido por cliente: prioridad -> people[].quotes -> cuota.person_requested -> 0
                            $monto_pedido = null;
                            if (!empty($people)) {
                                foreach ($people as $pp) {
                                    if ((string) ($pp->document ?? '') === (string) $person_document) {
                                        $monto_pedido = $pp->quotes ?? null;
                                        break;
                                    }
                                }
                            }
                            if (is_null($monto_pedido)) {
                                $monto_pedido = $first->person_requested ?? 0;
                            }

                            $processedDocuments[] = (string) $person_document;
                        @endphp

                        <tr>
                            <td style="text-align: left; padding-left: 8px;">{{ $person_name }}</td>
                            <td style="text-align: center;">{{ $person_document }}</td>
                            <td style="text-align: center;">S/. {{ number_format($monto_pedido, 2) }}</td>
                            <td style="text-align: center;">S/. {{ number_format($monto_a_pagar, 2) }}</td>
                            <td style="text-align: center;"></td>
                        </tr>
                    @endforeach

                    {{-- Incluir integrantes definidos en $people que no tengan cuotas --}}
                    @if (!empty($people))
                        @foreach ($people as $pp)
                            @php $doc = (string) ($pp->document ?? ''); @endphp
                            @if ($doc !== '' && !in_array($doc, $processedDocuments))
                                <tr>
                                    <td style="text-align: left; padding-left: 8px;">{{ $pp->name ?? 'N/A' }}</td>
                                    <td style="text-align: center;">{{ $pp->document ?? 'N/A' }}</td>
                                    <td style="text-align: center;">S/. {{ number_format($pp->quotes ?? 0, 2) }}</td>
                                    <td style="text-align: center;">S/. {{ number_format(0, 2) }}</td>
                                    <td style="text-align: center;"></td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                @else
                    @php
                        // Si no hay cuotas, listar miembros con columnas vacías para monto a pagar y firma
                        $members = [];
                        if (!empty($people) && count($people) > 0) {
                            foreach ($people as $p) {
                                $members[] = (object) [
                                    'name' => $p->name ?? null,
                                    'document' => $p->document ?? null,
                                    'quotes' => $p->quotes ?? 0,
                                ];
                            }
                        }
                    @endphp
                    @if (count($members) > 0)
                        @foreach ($members as $m)
                            <tr>
                                <td style="text-align: left; padding-left: 8px;">{{ $m->name ?? 'N/A' }}</td>
                                <td style="text-align: center;">{{ $m->document ?? 'N/A' }}</td>
                                <td style="text-align: center;">S/. {{ number_format($m->quotes ?? 0, 2) }}</td>
                                <td style="text-align: center;">S/. {{ number_format(0, 2) }}</td>
                                <td style="text-align: center;"></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" style="text-align: center;">No hay registros</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>
    @endif

    <div style="margin: 75px auto 0 auto; text-align: center;">
        <div style="border-top: 2px solid #000; width: 250px; margin: 0 auto; padding-top: 5px;">
            <p><strong><span class="empresa">APLICANDO CONFIANZA PERU S.A.C</span></strong></p>
        </div>
    </div>

    @foreach ($people as $i => $p)
        @if ($i % 2 == 0)
            <div style="margin-top: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
        @endif
        <td style="width: 50%; border: none; padding: 0; vertical-align: top;">
            <table style="border-collapse: collapse; margin-bottom: 8px;">
                <tr>
                    <td style="vertical-align: bottom; border: none; padding: 0;">
                        <br>
                    </td>
                    <td style="vertical-align: bottom; border: none; padding: 0 0 0 10px;">

                    </td>
                </tr>
            </table>
            <div style="border-top: 2px solid #000; width: 300px; margin: 0 auto; padding-top: 10px;">
                <p><strong>EL MUTUATARIO/ CLIENTES</strong></p>
                <p><strong>Nombre:</strong> {{ $p->name ?? 'Nombre Ejemplo' }}</p>
                <p><strong>DNI N°:</strong> {{ $p->document ?? '12345678' }}</p>
            </div>
        </td>
        @if ($i % 2 == 1 || $i == count($people) - 1)
            </tr>
            </table>
            </div>
        @endif
    @endforeach

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

    <p>Pagaré(mos), solidariamente, a la orden de la empresa <span class="empresa">APLICANDO CONFIANZA PERU
            S.A.C</span>, con RUC N° 20611409355, en la fecha de vencimiento indicada, la suma de S/
        {{ number_format($contract->payable_amount ?? 0, 2) }} soles, importe que corresponde a la liquidación de la
        suma que adeudo, en virtud al contrato de
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

    @foreach ($people as $i => $p)
        @if ($i % 2 == 0)
            <div style="margin-top: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
        @endif
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
                Nombre: {{ $p->name }}<br>
                DNI N°: {{ $p->document }}<br>
                Estado Civil: _________________________<br>
                Domicilio: {{ $p->address }}
            </p>
        </td>

        @if ($i % 2 == 1 || $i == count($people) - 1)
            </tr>
            </table>
            </div>
        @endif
    @endforeach
</body>

</html>
