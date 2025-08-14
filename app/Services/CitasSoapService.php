<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class CitasSoapService
{
    /**
     * URL del servicio SOAP para gestión de citas
     *
     * @var string
     */
    protected $wsdlUrl;

    /**
     * Usuario para autenticación
     *
     * @var string
     */
    protected $usuario;

    /**
     * Contraseña para autenticación
     *
     * @var string
     */
    protected $password;

    /**
     * Constructor del servicio
     */
    public function __construct(
        ?string $wsdlUrl = null,
        ?string $usuario = null,
        ?string $password = null
    ) {
        $this->wsdlUrl = $wsdlUrl ?? config('services.citas.wsdl_url', 'http://mitdesqafo.mitsuiautomotriz.com:8002/sap/bc/srt/wsdl/flv_10002A111AD1/bndg_url/sap/bc/srt/rfc/sap/zws_ges_cit/400/zws_gescit/zws_gescit?sap-client=400');
        $this->usuario = $usuario ?? config('services.citas.usuario', 'USR_TRK');
        $this->password = $password ?? config('services.citas.password', 'Srv0103$MrvTk%');
    }

    /**
     * Crear cliente SOAP con autenticación
     *
     * @throws SoapFault
     */
    protected function crearClienteSoap(): SoapClient
    {
        $opciones = [
            'login' => $this->usuario,
            'password' => $this->password,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]),
        ];

        try {
            $cliente = new SoapClient($this->wsdlUrl, $opciones);

            return $cliente;
        } catch (SoapFault $e) {
            Log::error('Error al crear cliente SOAP: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener lista de citas disponibles
     */
    public function getCitasDisponibles(string $fecha, string $sucursal): Collection
    {
        try {
            $cliente = $this->crearClienteSoap();

            // Parámetros para la solicitud
            $parametros = [
                'IT_FECHA' => $fecha,
                'IT_SUCUR' => $sucursal,
            ];

            // Ejecutar la llamada al método del servicio web
            $respuesta = $cliente->Z_GET_CITAS_DISPONIBLES($parametros);

            // Procesar y devolver los resultados
            return $this->procesarRespuestaCitas($respuesta);
        } catch (SoapFault $e) {
            Log::error('Error al obtener citas disponibles: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Agendar una cita
     */
    public function agendarCita(array $datosCita): array
    {
        try {
            $cliente = $this->crearClienteSoap();

            // Ejecutar la llamada al método del servicio web
            $respuesta = $cliente->Z_AGENDAR_CITA($datosCita);

            // Procesar y devolver la respuesta
            return [
                'exito' => true,
                'mensaje' => 'Cita agendada correctamente',
                'datos' => $respuesta,
            ];
        } catch (SoapFault $e) {
            Log::error('Error al agendar cita: '.$e->getMessage());

            return [
                'exito' => false,
                'mensaje' => 'Error al agendar cita: '.$e->getMessage(),
                'datos' => null,
            ];
        }
    }

    /**
     * Procesar la respuesta del servicio de citas
     *
     * @param  mixed  $respuesta
     */
    protected function procesarRespuestaCitas($respuesta): Collection
    {

        $items = collect();

        if (isset($respuesta->ET_CITAS) && isset($respuesta->ET_CITAS->item)) {
            $citas = $respuesta->ET_CITAS->item;

            // Si solo hay un item, convertirlo a array
            if (! is_array($citas)) {
                $citas = [$citas];
            }

            foreach ($citas as $cita) {
                $items->push([
                    'id' => (string) ($cita->ID_CITA ?? ''),
                    'fecha' => (string) ($cita->FECHA ?? ''),
                    'hora' => (string) ($cita->HORA ?? ''),
                    'asesor' => (string) ($cita->ASESOR ?? ''),
                    'disponible' => (bool) ($cita->DISPONIBLE ?? false),
                ]);
            }
        }

        return $items;
    }
}
