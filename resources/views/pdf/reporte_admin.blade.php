<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo_seccion ?? 'Reporte Gráfico - UniMap 3D' }}</title>
    <style>
        @page {
            margin: 26px 28px 30px 28px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Encabezado */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-logo {
            font-size: 19px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .header-sub {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #475569;
        }
        .header-meta strong {
            color: #0f172a;
        }

        /* Título de la Sección */
        .report-title-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #2563eb;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .report-title {
            font-size: 12.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }
        .report-desc {
            font-size: 9px;
            color: #64748b;
            margin-top: 3px;
            margin-bottom: 0;
        }

        /* Resumen de Métricas (KPIs) */
        .kpi-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 8px;
            text-align: center;
        }
        .kpi-num {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-label {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }

        /* Gráficas Estadísticas */
        .section-header {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .graficas-grid-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .grafica-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .grafica-box-header {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .grafica-box-content {
            padding: 8px;
            text-align: center;
            background: #ffffff;
        }
        .grafica-img {
            max-width: 100%;
            height: auto;
            max-height: 220px;
            display: inline-block;
        }

        /* Barras de Distribución Visual */
        .meter-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .meter-table th {
            background: #f8fafc;
            font-size: 8.5px;
            color: #475569;
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            text-transform: uppercase;
        }
        .meter-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9px;
            vertical-align: middle;
        }
        .meter-bar-container {
            background-color: #e2e8f0;
            border-radius: 3px;
            height: 9px;
            width: 100%;
            overflow: hidden;
        }
        .meter-bar-fill {
            height: 9px;
            border-radius: 3px;
        }

        /* Pie de página */
        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Pie de página fijo -->
    <div class="footer">
        UniMap Universal 3D &bull; Reporte Gráfico Ejecutivo &bull; Emisión: {{ $fecha_generacion }} &bull; Administrador: {{ $admin_responsable }}
    </div>

    <!-- Encabezado Principal -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: middle;">
                <div class="header-logo">UniMap Universal 3D</div>
                <div class="header-sub">Reporte Gráfico y Métricas Visuales del Campus</div>
            </td>
            <td class="header-meta" style="vertical-align: middle;">
                <div><strong>Fecha de Emisión:</strong> {{ $fecha_generacion }}</div>
                <div><strong>Generado por:</strong> {{ $admin_responsable }}</div>
                <div><strong>Módulo:</strong> {{ strtoupper($seccion) }}</div>
            </td>
        </tr>
    </table>

    <!-- Título de la Sección -->
    <div class="report-title-box">
        <h1 class="report-title">{{ $titulo_seccion ?? 'REPORTE GRÁFICO EJECUTIVO' }}</h1>
        <p class="report-desc">
            {{ $descripcion_seccion ?? 'Síntesis visual y gráfica de métricas clave recopiladas en la plataforma UniMap.' }}
        </p>
    </div>

    <!-- Métricas Clave (KPIs) -->
    @if(isset($kpis) && is_array($kpis) && count($kpis) > 0)
        <table class="kpi-table" cellpadding="0" cellspacing="0">
            <tr>
                @foreach($kpis as $kpi)
                    <td width="{{ floor(100 / count($kpis)) }}%">
                        <div class="kpi-card">
                            <div class="kpi-num" style="color: {{ $kpi['color'] ?? '#0f172a' }};">{{ $kpi['valor'] }}</div>
                            <div class="kpi-label">{{ $kpi['label'] }}</div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- GRÁFICAS ESTADÍSTICAS (IMÁGENES CAPTURADAS DEL CANVAS)         --}}
    {{-- ============================================================== --}}
    @if(isset($graficas) && is_array($graficas) && count($graficas) > 0)
        <div class="section-header">Visualización Gráfica Interactiva</div>
        <table class="graficas-grid-table" cellpadding="0" cellspacing="0">
            @php
                $chunks = array_chunk($graficas, 2);
            @endphp
            @foreach($chunks as $par)
                <tr>
                    @foreach($par as $g)
                        <td width="{{ count($par) === 1 ? '100%' : '50%' }}" style="padding: 4px; vertical-align: top;">
                            <div class="grafica-box">
                                <div class="grafica-box-header">{{ $g['titulo'] ?? 'Gráfica Analítica' }}</div>
                                <div class="grafica-box-content">
                                    <img src="{{ $g['imagen'] }}" class="grafica-img" alt="{{ $g['titulo'] ?? '' }}">
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif

    {{-- ============================================================== --}}
    {{-- SECCIÓN: USUARIOS (DISTRIBUCIÓN VISUAL)                       --}}
    {{-- ============================================================== --}}
    @if($seccion === 'usuarios')
        <div class="section-header">Distribución Porcentual de la Comunidad por Rol</div>
        @if(isset($distribucion_roles) && count($distribucion_roles) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="32%">Tipo de Usuario / Rol</th>
                        <th width="18%">Cantidad</th>
                        <th width="15%">Porcentaje</th>
                        <th width="35%">Proporción Gráfica</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_roles as $rol)
                        <tr>
                            <td><strong>{{ $rol['label'] }}</strong></td>
                            <td>{{ $rol['cantidad'] }} usuarios</td>
                            <td><strong>{{ $rol['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $rol['porcentaje'] }}%; background-color: {{ $rol['color'] ?? '#2563eb' }};"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(isset($distribucion_meses) && count($distribucion_meses) > 0)
            <div class="section-header">Historial de Nuevos Registros por Mes</div>
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="25%">Mes de Registro</th>
                        <th width="20%">Nuevos Registros</th>
                        <th width="15%">Porcentaje</th>
                        <th width="40%">Tendencia Gráfica</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_meses as $m)
                        <tr>
                            <td><strong>{{ $m['mes'] }}</strong></td>
                            <td>{{ $m['cantidad'] }} registros</td>
                            <td><strong>{{ $m['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ min(100, $m['porcentaje'] * 1.5) }}%; background-color: #3b82f6;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- ============================================================== --}}
    {{-- SECCIÓN: SESIONES (DISTRIBUCIÓN VISUAL)                        --}}
    {{-- ============================================================== --}}
    @if($seccion === 'sesiones')
        <div class="section-header">Distribución de Inicios de Sesión por Día de la Semana</div>
        @if(isset($distribucion_dias) && count($distribucion_dias) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="25%">Día de la Semana</th>
                        <th width="20%">Accesos Registrados</th>
                        <th width="15%">Porcentaje</th>
                        <th width="40%">Nivel de Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_dias as $d)
                        <tr>
                            <td><strong>{{ $d['dia'] }}</strong></td>
                            <td>{{ $d['cantidad'] }} accesos</td>
                            <td><strong>{{ $d['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ min(100, $d['porcentaje'] * 2) }}%; background-color: #6366f1;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-header">Plataformas y Dispositivos de Acceso</div>
        @if(isset($distribucion_dispositivos) && count($distribucion_dispositivos) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="32%">Tipo de Dispositivo</th>
                        <th width="18%">Sesiones</th>
                        <th width="15%">Porcentaje</th>
                        <th width="35%">Distribución Gráfica</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_dispositivos as $disp)
                        <tr>
                            <td><strong>{{ $disp['label'] }}</strong></td>
                            <td>{{ $disp['cantidad'] }} sesiones</td>
                            <td><strong>{{ $disp['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $disp['porcentaje'] }}%; background-color: {{ $disp['color'] ?? '#06b6d4' }};"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- ============================================================== --}}
    {{-- SECCIÓN: RECORRIDOS (DISTRIBUCIÓN VISUAL)                      --}}
    {{-- ============================================================== --}}
    @if($seccion === 'recorridos')
        <div class="section-header">Top Lugares y Salones con Mayor Demanda en el Mapa 3D</div>
        @if(isset($ranking_lugares) && count($ranking_lugares) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="10%">Puesto</th>
                        <th width="38%">Lugar / Salón Recorrido</th>
                        <th width="18%">Visitas</th>
                        <th width="34%">Demanda Relativa</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $maxVisitas = $ranking_lugares->first()?->total_visitas ?? 1;
                    @endphp
                    @foreach($ranking_lugares as $idx => $rl)
                        @php
                            $proporcion = $maxVisitas > 0 ? round(($rl->total_visitas / $maxVisitas) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td><strong>#{{ $idx + 1 }}</strong></td>
                            <td><strong>{{ $rl->lugar_mesh }}</strong></td>
                            <td>{{ $rl->total_visitas }} recorridos</td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $proporcion }}%; background-color: #f59e0b;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-header">Distribución de Búsquedas por Bloque / Edificio</div>
        @if(isset($distribucion_bloques) && count($distribucion_bloques) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="30%">Bloque / Zona del Campus</th>
                        <th width="20%">Consultas Realizadas</th>
                        <th width="15%">Porcentaje</th>
                        <th width="35%">Porcentaje Visual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_bloques as $bl)
                        <tr>
                            <td><strong>{{ $bl['bloque'] }}</strong></td>
                            <td>{{ $bl['cantidad'] }} búsquedas</td>
                            <td><strong>{{ $bl['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $bl['porcentaje'] }}%; background-color: #10b981;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- ============================================================== --}}
    {{-- SECCIÓN: REPORTES (DISTRIBUCIÓN VISUAL)                        --}}
    {{-- ============================================================== --}}
    @if($seccion === 'reportes')
        <div class="section-header">Estado de Gestión y Resolución de Incidencias</div>
        @if(isset($distribucion_estados) && count($distribucion_estados) > 0)
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="32%">Estado del Reporte</th>
                        <th width="18%">Cantidad</th>
                        <th width="15%">Porcentaje</th>
                        <th width="35%">Balance Gráfico</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_estados as $est)
                        <tr>
                            <td><strong>{{ $est['label'] }}</strong></td>
                            <td>{{ $est['cantidad'] }} reportes</td>
                            <td><strong>{{ $est['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $est['porcentaje'] }}%; background-color: {{ $est['color'] ?? '#3b82f6' }};"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(isset($distribucion_tipos) && count($distribucion_tipos) > 0)
            <div class="section-header">Clasificación de Fallas por Tipo de Error</div>
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="32%">Categoría del Problema</th>
                        <th width="18%">Incidencias</th>
                        <th width="15%">Porcentaje</th>
                        <th width="35%">Incidencia Relativa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($distribucion_tipos as $tipo)
                        <tr>
                            <td><strong>{{ $tipo['tipo'] }}</strong></td>
                            <td>{{ $tipo['cantidad'] }} errores</td>
                            <td><strong>{{ $tipo['porcentaje'] }}%</strong></td>
                            <td>
                                <div class="meter-bar-container">
                                    <div class="meter-bar-fill" style="width: {{ $tipo['porcentaje'] }}%; background-color: #ef4444;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(isset($registros_testers) && count($registros_testers) > 0)
            <div class="section-header">Directorio de Testers Registrados (Entorno de Pruebas)</div>
            <table class="meter-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="8%">ID</th>
                        <th width="24%">Usuario / Alias</th>
                        <th width="26%">Nombre del Tester</th>
                        <th width="22%">Dispositivo / Notas</th>
                        <th width="10%">Estado</th>
                        <th width="10%">Último Acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registros_testers as $t)
                        <tr>
                            <td>#{{ $t->id_tester }}</td>
                            <td><strong>{{ $t->username }}</strong></td>
                            <td>{{ $t->nombre }}</td>
                            <td>{{ $t->dispositivo ?? 'Dispositivo Web' }}</td>
                            <td>
                                <span style="font-weight: bold; color: {{ $t->activo ? '#16a34a' : '#dc2626' }};">
                                    {{ $t->activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </td>
                            <td>{{ $t->ultimo_acceso ? $t->ultimo_acceso->format('d/m/Y H:i') : 'Sin ingreso' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

</body>
</html>
