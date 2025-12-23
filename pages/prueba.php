<?
#Version 2025-11-10 by Nicolas
// error_reporting(E_ERROR);
// ini_set('display_errors', 1);
require_once dirname(__FILE__).('/lib/nusoap.php');
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::AppDir().'/classes/Inspeccion.php';
require_once Conf::FwDir().'/classes/Sesion.php';
require_once Conf::AppDir().'/classes/Log_HDI.php';
require_once Conf::AppDir().'/classes/Glosas.php';


	// $sesion = new Sesion('');

	// $codExterno = 111;
	// $codInterno = 222;
	// // $conf_corredor = Conf::IdCorredorHdiBE();
	// $conf_corredor = Conf::IdCorredorHdiDirecto();
	// $inspeccion = new Inspeccion($sesion);
	// $inspecciones_WS = $inspeccion->getInspeccionesWSRespuestaMagallanes($conf_corredor); //arreglo con inspecciones que se deben enviar al WS
	// // $inspecciones_WS = array(
	// // 	array(
	// // 		"id_inspeccion" => 2253655
	// // 	)
	// // );
    // $arr = Datos($inspecciones_WS);
    // Conexion($arr);

function Datos($inspecciones_WS){
    echo "Estamos aca";

    global $sesion;
	foreach($inspecciones_WS AS $obj)
		{
		//PARTES DEL JSON
		$arr = [];
		$lista_accesorios = [];
		$lista_repuestos = [];

	    $inspeccion = new Inspeccion($sesion);

		$id_inspeccion = $obj['id_inspeccion'];
		//$id_inspeccion = 1443761;
		$inspeccion->Load($id_inspeccion);
		$es_rechazo = $inspeccion->fields['id_estado_inspeccion'] == 8 ? true : false;
		$inspeccion_auto = new InspeccionAuto($sesion);
		$inspeccion_auto->LoadInspeccion($id_inspeccion);
		$id_corredor = $inspeccion->fields['id_corredor'];
		$rut_corredor = Utiles::Glosa2($sesion, $id_corredor,"rut","prm_corredor","id_corredor");
		$dv_corredor = Utiles::Glosa2($sesion, $id_corredor,"dv","prm_corredor","id_corredor");
		$codigo_rut = $rut_corredor . $dv_corredor;
		//OBTENER ID DE HDI
		$id_inspeccion_magallanes = $inspeccion->fields['id_inspeccion_compania'];
		$es_vehiculo_movil = $inspeccion_auto->fields['id_tipo_vehiculo']?true:false;
		if($id_inspeccion_magallanes > 0)
			$NumeroInspeccion = $id_inspeccion_magallanes;
		else{
			$NumeroInspeccion = $inspeccion->getNumeroInspeccionMagallanes();
			$inspeccion->Edit('id_inspeccion_compania',$NumeroInspeccion);
			$inspeccion->Write();
		}
		if($inspeccion->fields['id_punto_coordinacion'])
			$LugarInspeccion=1; //Punto Fijo
		elseif($inspeccion->fields['lugar_coordinacion'])
			$LugarInspeccion=0; //Domicilio
		else
			$LugarInspeccion=0; //Domicilio

		//ASEGURADO
		$id_asegurado = $inspeccion->fields['id_asegurado'];
		$Rut = Utiles::Glosa2($sesion, $id_asegurado,"rut","asegurado","id_asegurado");
		$dvRut = Utiles::Glosa2($sesion, $id_asegurado,"dv","asegurado","id_asegurado");
		$ApellidoPaterno = Utiles::Glosa2($sesion, $id_asegurado,"apellido1","asegurado","id_asegurado");
		$ApellidoMaterno = Utiles::Glosa2($sesion, $id_asegurado,"apellido2","asegurado","id_asegurado");
		$Nombres = Utiles::Glosa2($sesion, $id_asegurado,"nombre","asegurado","id_asegurado");
		$fonoparticular = Utiles::Glosa2($sesion, $id_asegurado,"telefono2","asegurado","id_asegurado");
		if(!$fonoparticular) $fonoparticular = "";
		$fonocomercial = Utiles::Glosa2($sesion, $id_asegurado,"telefono1","asegurado","id_asegurado");
		$email = Utiles::Glosa2($sesion, $id_asegurado,"email","asegurado","id_asegurado");
		$nombre_contacto = Utiles::Glosa2($sesion, $id_asegurado,"nombre_contacto","asegurado","id_asegurado");
		$telefono_contacto = Utiles::Glosa2($sesion, $id_asegurado,"telefono_contacto","asegurado","id_asegurado");
		$direccion = Utiles::Glosa2($sesion, $id_asegurado,"CONCAT(coalesce(direccion_calle,''),' ',coalesce(direccion_numero,''))","asegurado","id_asegurado");
		$id_comuna = Utiles::Glosa2($sesion, $id_asegurado, "id_comuna", "asegurado", "id_asegurado");
		$id_comuna_hdi = Utiles::Glosa2($sesion, $id_comuna, "id_comuna_hdi", "prm_comuna_hdi", "id_comuna_rac");
		if($id_comuna_hdi=="") $id_comuna_hdi = 0;
		$depto = Utiles::Glosa2($sesion, $id_asegurado,"direccion_depto","asegurado","id_asegurado");
		if($depto)
			$direccion .= " Depto  $depto";

		$id_comuna_coordinacion = $inspeccion->fields['id_comuna_coordinacion']?$inspeccion->fields['id_comuna_coordinacion']:Utiles::Glosa2($sesion, $inspeccion->fields['id_punto_coordinacion'], "id_comuna","prm_punto_coordinacion","id_punto_coordinacion");

		if(!$id_comuna_coordinacion)
				$id_comuna_coordinacion = Utiles::Glosa2($sesion, $inspeccion->fields['id_asegurado'], "id_comuna","asegurado","id_asegurado");

		$glosa_comuna = Utiles::Glosa2($sesion, $id_comuna_coordinacion,"glosa_comuna","prm_comuna","id_comuna");
		if($inspeccion->fields['id_estado_inspeccion'] == 4) $observaciones = "aprobada";
		elseif($inspeccion->fields['id_estado_inspeccion'] == 8) $observaciones = "rechazada";
		elseif($inspeccion->fields['id_estado_inspeccion'] == 5) $observaciones = "anulada";
		else $observaciones = "sin observaciones";

		$observaciones .= $inspeccion_auto->fields['observacion'];
		$glosa_motivo_rechazo = GetGlosaMotivoRechazo($sesion, $inspeccion->fields['id_motivo_rechazo']);

		$observaciones .= $glosa_motivo_rechazo .", Observacion Auditor: ". $inspeccion_auto->fields['observacion'];
		$observaciones .= "\n".ObtenerDeducibles($sesion, $inspeccion, $inspeccion_auto);

		$observaciones .= ".";
		$fecha_creacion = $inspeccion->fields['fecha_creacion']?$inspeccion->fields['fecha_creacion']:"1900/01/01";
		$fecha_ultimo_cambio = $inspeccion->getFechaUltimoCambio();
		$fecha_realizacion = $fecha_ultimo_cambio? $fecha_ultimo_cambio: "1900/01/01 00:00:00";
		$hora_realizacion = substr($fecha_realizacion,11,8);
		$fecha_creacion_anno = substr($fecha_creacion,0,4);
		$fecha_creacion_mes = substr($fecha_creacion,5,2);
		$fecha_creacion_dia = substr($fecha_creacion,8,2);
		$fecha_realizacion_anno = substr($fecha_realizacion,0,4);
		$fecha_realizacion_mes = substr($fecha_realizacion,5,2);
		$fecha_realizacion_dia = substr($fecha_realizacion,8,2);

		$fecha_creacion = "$fecha_creacion_dia/$fecha_creacion_mes/$fecha_creacion_anno";
		$fecha_realizacion = "$fecha_realizacion_dia/$fecha_realizacion_mes/$fecha_realizacion_anno $hora_realizacion";

		//VEHICULO
		$kilometraje = $inspeccion_auto->fields['kilometraje'] ? $inspeccion_auto->fields['kilometraje']:1;
		$patente = $inspeccion_auto->fields['patente']? $inspeccion_auto->fields['patente']: "";
		$nummotor = $inspeccion_auto->fields['numero_motor']? $inspeccion_auto->fields['numero_motor']:1;
		$chasis = str_pad($inspeccion_auto->fields['numero_chassis'], 17, '0', STR_PAD_LEFT)? str_pad($inspeccion_auto->fields['numero_chassis'], 17, '0', STR_PAD_LEFT):"11111111111111111";
		if($es_rechazo && !$inspeccion_auto->fields['numero_puertas']) $puertas = 1;
		else $puertas = $inspeccion_auto->fields['numero_puertas']?$inspeccion_auto->fields['numero_puertas']:0;

		$anno = $inspeccion_auto->fields['anno']?$inspeccion_auto->fields['anno']:0;
		$id_color = $inspeccion_auto->fields['id_color']?$inspeccion_auto->fields['id_color']:"";

		$codigo_color = Utiles::Glosa2($sesion, $id_color,"codigo_magallanes","prm_color","id_color");
		$color = Utiles::Glosa2($sesion, $codigo_color, "glosa_hdi", "prm_color_hdi", "id_color_hdi");
		$id_marca = Utiles::Glosa2($sesion, $id_inspeccion,"id_marca_auto","inspeccion_auto","id_inspeccion");
		$id_modelo = Utiles::Glosa2($sesion, $id_inspeccion,"id_modelo","inspeccion_auto","id_inspeccion");
		$id_version = Utiles::Glosa2($sesion, $id_inspeccion,"id_version_auto","inspeccion_auto","id_inspeccion");
		$glosa_marca = Utiles::Glosa2($sesion, $id_marca,"glosa", "prm_marca_auto", "id_marca_auto");
		$glosa_modelo = Utiles::Glosa2($sesion, $id_modelo,"glosa_modelo_auto","prm_modelo_auto","id_modelo");
		if($id_version > 0) $codigo_modelo = Utiles::Glosa2($sesion, $id_version,"codigo_magallanes","prm_version_auto","id_version_auto");
		$id_tipo_auto = Utiles::Glosa2($sesion, $id_modelo,"id_tipo_auto","prm_modelo_auto","id_modelo");
		if($id_tipo_auto > 0) $codigo_tipo_auto = Utiles::Glosa2($sesion, $id_tipo_auto,"codigo_magallanes","prm_tipo_auto","id_tipo_auto");

		$id_estado_inspeccion = $inspeccion->fields['id_estado_inspeccion'];
		$semaforo = "V";
		$obs_semaforo = "-";
		if($id_estado_inspeccion == 4) $EstadoInspeccion = 4;
		elseif($id_estado_inspeccion == 8) $EstadoInspeccion = 3;
		elseif($id_estado_inspeccion == 5) $EstadoInspeccion = -1;

		$id_uso_auto = Utiles::Glosa2($sesion, $id_inspeccion,"id_uso_auto","inspeccion_auto","id_inspeccion");
		if($id_uso_auto == 2){//Comercial
			$id_tipo_vehiculo_comercial = $es_rechazo && !$inspeccion_auto->fields['id_tipo_vehiculo_comercial'] ? 7 : Utiles::Glosa($sesion, $id_inspeccion,"id_tipo_vehiculo_comercial","inspeccion_auto","id_inspeccion");
			$id_uso_auto = Utiles::Glosa2($sesion, $id_tipo_vehiculo_comercial,"cod_magallanes","prm_tipo_vehiculo_comercial","id_tipo_vehiculo_comercial");
		}
		if($id_uso_auto == "") $id_uso_auto = 0;

		$id_origen = Utiles::Glosa2($sesion, $id_inspeccion,"id_conclusion_inspeccion","inspeccion_auto","id_inspeccion");
		if($id_origen == 3) $cod_origen = True; //Importacion Directa
		else $cod_origen = False;

		//EQUIPO MOVIL
		$id_tipo_em = $inspeccion_auto->fields['id_tipo_vehiculo'] != null ? Utiles::Glosa2($sesion, $inspeccion_auto->fields['id_tipo_vehiculo'],"id_tipo_equipo_movil","prm_tipo_equipo_movil","id_tipo_equipo_movil") : 0;
		$id_tipo_uso_em = $inspeccion_auto->fields['id_uso_vehiculo_em'] != null ? Utiles::Glosa2($sesion, $inspeccion_auto->fields['id_uso_vehiculo_em'],"id_tipo_uso_em","prm_tipo_uso_em","id_tipo_uso_em") : 0;
		$marca_em = $inspeccion_auto->fields['glosa_marca'] != null? $inspeccion_auto->fields['glosa_marca'] : '';
		$modelo_em = $inspeccion_auto->fields['glosa_modelo'] != null ? $inspeccion_auto->fields['glosa_modelo'] : '';
		$chasis_em = $inspeccion_auto->fields['numero_chassis'] != null ? $inspeccion_auto->fields['numero_chassis'] : '11111111111111111';
		$anio_em = $inspeccion_auto->fields['anno'] != null ? $inspeccion_auto->fields['anno'] : 0;
		$patente_em = $inspeccion_auto->fields['patente'] != null ? $inspeccion_auto->fields['patente'] : '';
		$color_em = $inspeccion_auto->fields['id_color'] != null ? Utiles::Glosa2($sesion, $inspeccion_auto->fields['id_color'],"glosa","prm_color","id_color") : '';
		$descripcion_em = $inspeccion_auto->fields['observacion'] != null ? $inspeccion_auto->fields['observacion'] : '';

		//DANOS
		$partes_con_danos = $inspeccion->getPartesDanadasMagallanesFinal($inspeccion_auto->fields['id_inspeccion_auto']);
		if($partes_con_danos){
			foreach($partes_con_danos as $partes){
				$nivelDano = $partes['id_nivel_dano'];
				$tipoDano = $partes['cod_dano_magallanes'];
				$piezaDano = $partes['cod_parte_magallanes'];
				$glosaPiezaDano = $partes['glosa_parte_sistema'];
				$deducibleDano = $partes['deducible_uf'];

				$sinCobertura = $partes['glosa_nivel_dano'] == "Exclusion" || $partes['glosa_nivel_dano'] == "Excluido" ? 1:0;

				$observacion = $partes['obs'];
				$id_estado_uso = $partes['id_estado_uso'];
				$glosa_uso=0;
				if($id_estado_uso > 0)
					$glosa_uso = Utiles::Glosa2($sesion, $id_estado_uso,"glosa_estado_uso","prm_estado_uso","id_estado_uso");

				if($nivelDano > 3)
					$nivelDano = 99;

				if(stristr($observacion, ';') === FALSE)
					$observacion = "-";

				if($glosa_uso != "")
				{
					if($observacion == "-")
						$observacion = "Estado/Uso: $glosa_uso";
					else
						$observacion = "Estado/Uso: $glosa_uso; $observacion";
					unset ($glosa_uso);
				}

				if($glosaPiezaDano){
					$lista_repuestos[] = [
						"CodigoRepuesto" => $piezaDano,
						"CodigoTipoDano" => $tipoDano,
						"CodigoTipoGravedad" => $nivelDano,
						"Observacion" => $observacion,
						"deducibleDano"=> $deducibleDano,
          	"sinCobertura"=> $sinCobertura
					];
				}
			}
		}


		//ACCESORIOS
		$accesorios = $inspeccion->getAccesoriosInspeccionMagallanes($inspeccion_auto->fields['id_inspeccion_auto']);

		if($accesorios){
			$espejos_encontrados = 0;
			$conflicto_sensores = array();
			foreach($accesorios as $acc){
				$codigo_accesorio = "";
				$id_accesorio = $acc['id_accesorio'];
				$cantidad = $acc['cantidad'];
				if($cantidad == 0)
					$cantidad = 1;
				$id_estado_uso = $acc['id_estado_uso'];;
				$glosa_uso = Utiles::Glosa2($sesion, $id_estado_uso,"glosa_estado_uso","prm_estado_uso","id_estado_uso");
				$detalle = $acc['detalle_accesorio'];
				if($detalle == "Sin informacion")
					$detalle = "";
				if($glosa_uso != "")
					$detalle = "$glosa_uso $detalle";
				if($id_accesorio == 34 || $id_accesorio == 114)//Espejos Electricos
					$detalle .= " Electrico";
				if($detalle == "")
					$detalle = "-";
				$id_accesorio_modelo = $acc['id_accesorio_modelo'];
				$codigo_accesorio = Utiles::Glosa2($sesion, $id_accesorio,"cod_magallanes","prm_accesorio","id_accesorio");
				$glosa_accesorio = Utiles::Glosa2($sesion, $id_accesorio,"glosa_accesorio","prm_accesorio","id_accesorio");
				$id_accesorio_modelo = $acc['id_accesorio_modelo'];
				$id_modelo_accesorio = Utiles::Glosa2($sesion, $id_accesorio_modelo,"id_modelo","prm_accesorio_modelo","id_accesorio_modelo");
				if($id_modelo_accesorio > 0)
				{
					$glosa_modelo_accesorio = Utiles::Glosa2($sesion, $id_modelo_accesorio,"glosa_modelo_auto","prm_modelo_auto","id_modelo");
					$id_marca_accesorio = Utiles::Glosa2($sesion, $id_modelo_accesorio, "id_marca_auto", "prm_modelo_auto", "id_modelo");
					$glosa_marca_accesorio = Utiles::Glosa2($sesion, $id_marca_accesorio,"glosa", "prm_marca_auto", "id_marca_auto");
				}
				else
				{
					$glosa_marca_accesorio = "-";
					$glosa_modelo_accesorio = "-";

				}

				if($codigo_accesorio > 0)
				{
					$es_repuesto = Utiles::Glosa2($sesion, $id_accesorio,"repuesto_magallanes","prm_accesorio","id_accesorio");
					if($id_accesorio == 3 || $id_accesorio == 34 || $id_accesorio == 114)
					{
						if($espejos_encontrados)
							continue;

						if($cantidad == 1)
						{
							$lista_accesorios[] = [
								"CodigoAccesorio" =>'26',
								"EstaPresente" => '1',
								"Cantidad" => '1',
								"Marca" => $glosa_marca_accesorio,
								"Modelo" => $glosa_modelo_accesorio,
								"CodigoTipoDano" => '0',
								"CodigoTipoGravedad" => '99',
								"Observacion" => utf8_encode($detalle)
							];
						}
						else
						{
							$lista_accesorios[] = [
								"CodigoAccesorio" =>'25',
								"EstaPresente" => '1',
								"Cantidad" => '1',
								"Marca" => $glosa_marca_accesorio,
								"Modelo" => $glosa_modelo_accesorio,
								"CodigoTipoDano" => '0',
								"CodigoTipoGravedad" => '99',
								"Observacion" => utf8_encode($detalle)
							];

							$lista_accesorios[] = [
								"CodigoAccesorio" =>'26',
								"EstaPresente" => '1',
								"Cantidad" => '1',
								"Marca" => $glosa_marca_accesorio,
								"Modelo" => $glosa_modelo_accesorio,
								"CodigoTipoDano" => '0',
								"CodigoTipoGravedad" => '99',
								"Observacion" => utf8_encode($detalle)
							];
						}
						$espejos_encontrados = 1;
					}
					elseif($id_accesorio == '58')
					{
						$conflicto_sensores[]=58;
					}
					elseif($id_accesorio == '60')
					{
						$conflicto_sensores[]=60;
					}
					elseif($id_accesorio == '111')
					{
						$conflicto_sensores[]=111;
					}
					else
					{
						$lista_accesorios[] = [
							"CodigoAccesorio" => $codigo_accesorio,
							"EstaPresente" => '1',
							"Cantidad" => $cantidad,
							"Marca" => $glosa_marca_accesorio,
							"Modelo" => $glosa_modelo_accesorio,
							"CodigoTipoDano" => '0',
							"CodigoTipoGravedad" => '99',
							"Observacion" => utf8_encode($detalle)
						];
					}
				}
			}
		}
		if(in_array(60,$conflicto_sensores) || in_array(111,$conflicto_sensores)){
			$lista_accesorios[] = [
				"CodigoAccesorio" => '91',
				"EstaPresente" => '1',
				"Cantidad" => '1',
				"Marca" => 'Original',
				"Modelo" => 'Original',
				"CodigoTipoDano" => '0',
				"CodigoTipoGravedad" => '99',
				"Observacion" => '-'
			];
		}
					//PIEZAS DANADAS QUE VAN EN LOS ACCESORIOS
		$partes_con_danos = $inspeccion->getAccesoriosDanadosMagallanesFinal($inspeccion_auto->fields['id_inspeccion_auto']);
		if($partes_con_danos){
			foreach($partes_con_danos as $partes){
				$nivelDano = $partes['id_nivel_dano'];
				$tipoDano = $partes['cod_dano_magallanes'];
				$piezaDano = $partes['cod_parte_magallanes'];
				$glosaPiezaDano = $partes['glosa_parte_sistema'];
				$observacion = $partes['acc_danado'];

				if($nivelDano > 3) $nivelDano = 99;

				if(stristr($observacion, ';') === FALSE) $observacion = "-";
				if($glosaPiezaDano) {
					$lista_accesorios[] = [
						"CodigoAccesorio" => $piezaDano,
						"EstaPresente" => '1',
						"Cantidad" => '1',
						"Marca" => '-',
						"Modelo" => '-',
						"CodigoTipoDano" => $tipoDano,
						"CodigoTipoGravedad" => $nivelDano,
						"Observacion" => utf8_encode($observacion)
					];
				}

			}
		}

		if($inspeccion->fields['id_corredor'] == Conf::IdCorredorHdiBE() && $inspeccion->fields['id_inspeccion_padre']){
			$arr['CodigoInspeccionExterno'] = (int)$inspeccion->fields['id_inspeccion_padre'];
		}else if($inspeccion->fields['id_corredor'] == Conf::IdCorredorHdiBE() && $id_inspeccion_antigua = $inspeccion->getInspeccionAntigua()){
			$arr['CodigoInspeccionExterno'] = (int)$id_inspeccion_antigua;
			$NumeroInspeccion =  Utiles::Glosa2($sesion, $id_inspeccion_antigua, "id_inspeccion_compania", "inspeccion", "id_inspeccion");
			$inspeccion->Edit("id_inspeccion_compania", $NumeroInspeccion);
			$inspeccion->Write();
		}else{
			$arr['CodigoInspeccionExterno'] = (int)$id_inspeccion;
		}
		$arr['CodigoInspeccionInterno'] = $NumeroInspeccion;
		$arr['RutEmpresaInspectora'] = 760369292;
		$arr['NombreEmpresaInspectora'] = 'RAC';
		$arr['Detalle'] = [
			"LugarInspeccion" => $LugarInspeccion,
			"RutCliente" => $Rut,
			"DigitoVerificadorRutCliente" => $dvRut,
			"FechaInspeccion" => $fecha_realizacion,
			"NombreAsegurado" => utf8_encode($Nombres.' '.$ApellidoPaterno.' '.$ApellidoMaterno),
			"Telefono" => $fonoparticular,
			"EstadoInspeccion" => $EstadoInspeccion,
			"RutCorredor" => (int)substr($codigo_rut,0,8),
			"IdInspector" => 10002562,
			"NombreInspector" => 'rac',
			"MotivoInspeccion" => 1,
			"FechaSolicitud" => $fecha_creacion,
			"Semaforo" => $semaforo,
			"SemaforoObservacion" => "-"

		];
		if(!$es_vehiculo_movil){
			$arr['Vehiculo'] = [
				"CodigoTipoVehiculo" => (int)$codigo_tipo_auto,
				"CodigoModeloEspecifico" => (int)$codigo_modelo,
				"Patente" => $patente,
				"Ano" => $anno,
				"NumeroMotor" => $nummotor,
				"NumeroChasis" => $chasis,
				"VIN" => '1', //REVISAR
				"Color" => $color,
				"NumeroPuertas" => $puertas,
				"Uso" => utf8_encode($id_uso_auto),
				"Kilometraje" => $kilometraje,
				"ImportacionDirecta" => $cod_origen,
				"Direccion" => utf8_encode($direccion),
				"CodigoComuna" => $id_comuna_hdi
			];
			$arr['EquipoMovil'] = null;
		}else{
			$arr['Vehiculo'] = null;
			$arr['EquipoMovil'] = [
				"IdTipoEM" => (int)$id_tipo_em,
				"IdTipoUsoEM" => (int)$id_tipo_uso_em,
				"Marca" => $marca_em,
				"Modelo" => $modelo_em,
				"Serie" => $chasis_em,
				"AnioFabricacion" => $anio_em, //REVISAR
				"Patente" => $patente_em,
				"Color" => $color_em,
				"Descripcion" => $descripcion_em,
				"Direccion" => utf8_encode($direccion),
				"CodigoComuna" => $id_comuna_hdi
			];
		}

		$arr["listaAccesorios"] = $lista_accesorios;
		$arr["ListaRepuestos"] = $lista_repuestos;
		$arr["ListaObservaciones"] = [
			array(
				"ObservacionInspeccion" => utf8_encode(strip_tags($observaciones)),
				"ObservacionCliente" => "-"
				)

		];
        print_r($arr);
		return json_encode($arr);
	}
}
function Conexion($json){
	global $sesion, $id_estado_inspeccion, $NumeroInspeccion;
    // exit;
    $inspeccion = new Inspeccion($sesion);
    //$id_inspeccion = 1443761;
    $arr = json_decode($json, true);

    $id_inspeccion = $arr['CodigoInspeccionExterno'];
    echo "\ncodExterno: $id_inspeccion\n";
    if(!$inspeccion->Load($id_inspeccion)) return;
	$intentos = $inspeccion->fields['intentos'];
	$intentos = $intentos + 1;
	$inspeccion->Edit('intentos',$intentos);
	$inspeccion->Write();
	//$link = "https://rcdev.hdi.cl/esb-ws-gtw/Inspeccion/api/SetInspeccion"; //Produ
	// $link = "https://rcqa.hdi.cl/esb-ws-gtw/Inspeccion/api/SetInspeccion"; //QA
	$link = "https://wsfs.hdi.cl/esb-ws-gtw/Inspeccion/api/SetInspeccion"; //Prod

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	// $authorization = "Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1lIjoicmFjIiwiZXhwIjoxOTY5ODk3NTk2fQ.qdC09f3TqqxkwF9C0L7DK29z2lcQxnPtbqQMi8FutoQkgmMSXqPz-YfWu2egiqFyBEQSSX7NNREZ1L5wkxpihA"; //QA
	//$authorization = "Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1lIjoiTWFyZ2l0UyIsImV4cCI6MTk2ODM2MTk0MH0.IT15SyHAlnSQB320jHb6EdFBS94QCaC2SrXLJuY17r0ug7TCnyXbAnwdqWuw5urUUm2w5nOvqk1mu06EYo3Zcg" //PRODUCCION
	$authorization = "Authorization: Bearer eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1lIjoiNzYxOTkyMTgtSyIsImV4cCI6MTk3MzIxNjUyMX0.4-IdaXMLsYYJX6LC6yH03m4uNtV6Qzi54JyTFc0VusEO_56nuX-ZZwadyljnH7PaGg-cYXkHzqypfCaIYtFi3w"; //PRODUCCION

	$headers = array(
		"Content-Type: application/json",
		$authorization,
	);
	var_dump($json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_URL, $link);

	$respuesta = curl_exec($ch);
	var_dump($respuesta);
	$arreglo = json_decode($respuesta, true);

	$log_ws = new Log_HDI($sesion);
	$log_ws->Edit('id_ingreso',2);
	$log_ws->Edit('id_inspeccion',$inspeccion->fields['id_inspeccion']);
	$log_ws->Edit('id_inspeccion_hdi',$NumeroInspeccion);
	$log_ws->Edit('fecha_ingreso',date('Y-m-d'));
	$log_ws->Edit('hora_ingreso',date('H:i:s'));
	$log_ws->Edit('contenido',$json);
	$log_ws->Edit('respuesta',utf8_decode($respuesta));
	$log_ws->Edit('id_estado',$id_estado_inspeccion);

	if(is_array($arreglo)){
		$respuesta_estado = $arreglo["Result"]["respuesta"]["ESTADO"];
		$respuesta_des_estado = $arreglo["Result"]["respuesta"]["DES_ESTADO"];
		$respuesta_proceso = $arreglo["Result"]["respuesta"]["PROCESO"];
		$respuesta_lista_errores = array_map("utf8_decode",$arreglo["Result"]["respuesta"]["LISTA_ERRORES"]);
		$respuesta_mensaje = utf8_decode($arreglo["Mensaje"]);
		if($respuesta_lista_errores == null) $respuesta_lista_errores = array($respuesta_mensaje);
	}

	if($respuesta_estado == 1 && $respuesta_des_estado == "OK"){
		echo "Envio Exitoso\n";
		$inspeccion->Edit('envio_ws', 1);
		$inspeccion->Edit('fecha_envio_ws',date('Y-m-d'));
		$inspeccion->Edit('hora_envio_ws',date('H:i:s'));
		$inspeccion->Write();
		$inspeccion->crearLogInspeccion($sesion,4,$id_inspeccion,"Envio mediante WS a HDI exitoso",'');
		$log_ws->Edit('exitoso',1);
	}
	else{
		echo "Error en envio - respuesta=\n";
		$inspeccion->crearLogInspeccion($sesion,4,$id_inspeccion,"Envio mediante WS a HDI Fallido. Error: ". implode("\n", $respuesta_lista_errores),'');
		$log_ws->Edit('exitoso',0);
	}
	curl_close($ch);


	$log_ws->Write();
	exit;

	unset($id_inspeccion);
	unset($partes_con_danos);
	unset($partes);
	unset($accesorios);
	unset($acc);
	unset($intentos);
	unset($respuesta);
	unset($arreglo);
	unset($respuesta_estado);
}
function ObtenerDeducibles($sesion, $inspeccion, $inspeccion_auto) //TODO Homologar?
{
	$id_inspeccion = $inspeccion->fields['id_inspeccion'];
	$id_inspeccion_auto = $inspeccion_auto->fields['id_inspeccion_auto']; // 1423696
	$id_aseguradora = $inspeccion->fields['id_aseguradora'];

	//CLASE DEL AUTO DE LA INSPECCION
	$query_clase = "SELECT pmo.clase_modelo AS clase_auto
					FROM inspeccion i
					LEFT JOIN inspeccion_auto ia ON i.id_inspeccion = ia.id_inspeccion
					LEFT JOIN prm_modelo_auto pmo ON pmo.id_modelo = ia.id_modelo
					WHERE i.id_inspeccion = $id_inspeccion";
	$resp_clase = mysql_query($query_clase, $sesion->dbh) or Utiles::errorSQL($query_clase, __FILE__, __LINE__, $sesion->dbh);
	$row_clase = mysql_fetch_object($resp_clase);

	$id_clase_auto = $row_clase->clase_auto;

	$tipo_riesgo = $inspeccion->fields['id_tipo_riesgo'];
	//! REVISAR UF
	//TODO REVIRAR UF
	$sql = "SELECT
			ap.id_auto_parte,
			pnd.id_nivel_dano,
			pps.glosa_parte_sistema,
			pps.id_parte_sistema,
			pd.tipo AS tipo_dano,
			pd.id_danos,
			pnd.glosa_nivel_dano
	FROM auto_parte ap
	LEFT JOIN prm_parte_sistema pps ON pps.id_parte_sistema = ap.id_parte
	LEFT JOIN autoparte_dano apd ON apd.id_auto_parte = ap.id_auto_parte
	LEFT JOIN prm_nivel_dano pnd ON apd.id_nivel_dano = pnd.id_nivel_dano
	LEFT JOIN prm_dano pd ON pd.id_danos = apd.id_danos
	WHERE ap.id_inspeccion_auto = '$id_inspeccion_auto'
	";

	$resp = mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $sesion->dbh);

	//TOTAL DEDUCIBLES

	$query2 = "SELECT SUM(prm_deducibles.deducible_uf)
	FROM inspeccion, inspeccion_auto, auto_parte, autoparte_dano, prm_deducibles, prm_modelo_auto
	WHERE inspeccion.id_inspeccion = '$id_inspeccion'
	AND inspeccion.id_inspeccion = inspeccion_auto.id_inspeccion
	AND autoparte_dano.id_nivel_dano <> 4
	AND inspeccion_auto.id_inspeccion_auto = auto_parte.id_inspeccion_auto
	AND auto_parte.id_auto_parte = autoparte_dano.id_auto_parte
	AND inspeccion_auto.id_modelo = prm_modelo_auto.id_modelo

	AND inspeccion.id_aseguradora = prm_deducibles.id_aseguradora
	AND auto_parte.id_parte = prm_deducibles.id_parte_sistema
	AND autoparte_dano.id_nivel_dano = prm_deducibles.id_nivel_dano
	AND prm_modelo_auto.clase_modelo = prm_deducibles.id_clase_auto";

	$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
	//debug($row_clase,__LINE__);
	$contador_piezas = 0;
	while (($row = mysql_fetch_assoc($resp)) != null) {

		$id_parte_sistema = $row['id_parte_sistema'];
		$id_nivel_dano = $row['id_nivel_dano'];
		$id_danos = $row['id_danos'];

		//RESTO
		if ($id_nivel_dano) {
			$query_deducible = "SELECT
														deducible_uf
														FROM
																						prm_deducibles
														WHERE
														id_aseguradora = $id_aseguradora
														AND id_parte_sistema = $id_parte_sistema
														AND id_clase_auto = $id_clase_auto
														AND id_nivel_dano = $id_nivel_dano";
		}

		if ($query_deducible) {

			$resp_deducible = mysql_query($query_deducible, $sesion->dbh) or Utiles::errorSQL($query_deducible, __FILE__, __LINE__, $sesion->dbh);
			$row_deducible = mysql_fetch_object($resp_deducible);
			$deducible = $row_deducible->deducible_uf;

			$arreglo_deducibles["parte"][$id_parte_sistema] =
					[
					"deducible" => $deducible ? $deducible : 0,
			];
			$deducible = !empty($deducible) ? $deducible : 0;
			$contador_piezas++;
			$observacion .= $contador_piezas . " " . $row['glosa_parte_sistema'] . ", " . $row['tipo_dano'] . ", " . $row['glosa_nivel_dano'] . ", deducible uf " . $deducible . " \n";
		}

	}


	if (list($total) = mysql_fetch_array($resp2)) {
		if ($total) {
			$arreglo_deducibles['total'] = $total;
		} else {
			$arreglo_deducibles['total'] = 0;
		}
	}

	return $observacion;
}
