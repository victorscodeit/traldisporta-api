<?php
/**
 *
 * @About:      API Interface
 * @File:       index.php
 * @Date:       $Date:$ Jun-2023
 * @Version:    $Rev:$ 1.0
 * @Developer:  Cristian Margall (support@openmindsystems.com.es)
 **/

date_default_timezone_set("Europe/Madrid");
require '../class/external/Agenda.php';
require '../class/external/Compania.php';
require '../class/external/Centro.php';
require '../class/external/Categoria.php';
require '../class/external/Seccion.php';
require '../class/external/Sector.php';
require '../class/external/Postal.php';
require '../class/external/Registro.php';
require '../class/external/Vencimiento.php';

class DbHandlerExternal
{

    private $conn;

    function __construct()
    {
        require_once dirname(__FILE__) . './DbConnectExternal.php';
        // opening db connection
        $db = new DbConnectExternal();
        $this->conn = $db->connect();
    }

    public function getAgenda($agendaType = '')
    {
        /*
            $agendaType == "ALL" OR '' -> TOTA L'AGENDA
            $agendaType == "CUS" -> NOMES CLIENTS
            $agendaType == "SUP" -> NOMES PROVEIDORS
        */
        $t = "";
        if ($agendaType == 'CUS') {
            $t = " AND " . Agenda::ES_CLIENTE . " = 'S'  AND " . Agenda::ES_PROVEEDOR . " = 'N' ";
        }
        if ($agendaType == 'SUP') {
            $t = " AND " . Agenda::ES_CLIENTE . " = 'N'  AND " . Agenda::ES_PROVEEDOR . " = 'S' ";
        }
        if ($agendaType == '' || $agendaType == 'ALL') {
            $t = "";
        }

        $sql = "
        SELECT " . Agenda::HOLDINGCODE . " as holding, 
        " . Agenda::ID . " as id, 
        RTRIM(v." . Vat::NOMBRE_FISCAL . ") as fiscal_name, 
        RTRIM(" . Agenda::NOMBRE . ") as name,  
        " . Agenda::ES_CLIENTE . " as is_customer, 
        " . Agenda::ES_PROVEEDOR . " as is_supplier, 
        RTRIM(a." . Agenda::NIF . ") as vat,  
        " . Agenda::SECTOR . " as sector_id,  
        " . Agenda::CATEGORIA . " as category_id, 
        " . Agenda::EMPRESA_ID . " as company_id, 
        RTRIM(" . Agenda::CUENTA_CLIENTE . ") as customer_account,  
        RTRIM(" . Agenda::CUENTA_PROVEEDOR . ") as supplier_account, 
        RTRIM(" . Agenda::DIRECION . ") as address,  
        a." . Agenda::CODIGO_PAIS . " as country_id, 
        RTRIM(a." . Agenda::CP . ") as zip, 
        a." . Agenda::CODIGO_CIUDAD . " as city_id, 
        RTRIM(p." . Postal::NOMBRE_CIUDAD . ") as city_name, 
        RTRIM(" . Agenda::APARTADO_CORREOS . ") as post_apart,  
        RTRIM(" . Agenda::TELEFONO_TRAFICO . ") as trafic_phone,  
        RTRIM(" . Agenda::FAX_TRAFICO . ") as trafic_fax,  
        RTRIM(" . Agenda::EMAIL_TRAFICO . ") as trafic_email, 
        RTRIM(" . Agenda::CONTACTO_TRAFICO . ") as trafic_contact,  
        RTRIM(" . Agenda::TELEFONO_ADMINISTRACION . ") as administration_phone,  
        RTRIM(" . Agenda::FAX_ADMINISTRACION . ") as administration_fax,    
        RTRIM(" . Agenda::EMAIL_ADMINISTRACION . ")as administration_email,  
        RTRIM(" . Agenda::CONTACTO_ADMINISTRACION . ") as administration_contact,   
        RTRIM(" . Agenda::TELEFONO_COMERCIAL . ") as comercial_phone, 
        RTRIM(" . Agenda::FAX_COMERCIAL . ") as comercial_fax,  
        RTRIM(" . Agenda::EMAIL_COMERCIAL . ") as comercial_email,  
        RTRIM(" . Agenda::CONTACTO_COMERCIAL . ") as comercial_contact, 
        RTRIM(" . Agenda::OBSERVACIONES . ") as comments, 
        " . Agenda::CODIGO_BANCO_CLIENTE . " as bank_code_customer,  
        RTRIM(" . Agenda::NOMBRE_BANCO_CLIENTE . ") as bank_name_customer, 
        RTRIM(" . Agenda::IBAN_CLIENTE . ") as iban_customer,
        " . Agenda::CODIGO_BANCO_PROVEEDOR . " as bank_code_supplier, 
        RTRIM(" . Agenda::NOMBRE_BANCO_PROVEEDOR . ") as bank_name_supplier, 
        RTRIM(" . Agenda::IBAN_PROVEEDOR . ") as iban_supplier,        
        " . Agenda::PADRE_ID . " as parent_id,  
        RTRIM(" . Agenda::PADRE_NOMBRE . ") as parent_name,
        RTRIM(" . Agenda::NOMBRE_LOGISTICA . ") as logistic_name,
        RTRIM(" . Agenda::DIRECCION_LOGISTICA . ") as logistic_address,
        " . Agenda::CODIGO_PAIS_LOGISTICA . " as logistic_country_code,
        " . Agenda::ISO_PAIS_LOGISTICA . " as logistic_country_iso,
        RTRIM(" . Agenda::CP_LOGISTICA . ") as logistic_zip,
        RTRIM(" . Agenda::CIUDAD_LOGISTICA . ") as logistic_city,
        RTRIM(" . Agenda::RECOGIDA1_LOGISTICA . ") as logistic_picking1,
        RTRIM(" . Agenda::RECOGIDA2_LOGISTICA . ") as logistic_picking2,
        RTRIM(" . Agenda::ENTREGA1_LOGISTICA . ") as logistic_delivery1,
        RTRIM(" . Agenda::ENTREGA2_LOGISTICA . ") as logistic_delivery2,
        " . Agenda::CENTRO_PROPIETARIO . " as center_owner, 
        " . Agenda::CENTRO_DISTRIBUCION . " as center_distribution, 
        " . Agenda::AREA_DISTRIBUCION_ENTREGA . " as area_delivery, 
        " . Agenda::AREA_DISTRIBUCION_RECOGIDA . " as area_picking, 
        RTRIM(" . Agenda::TELF_LOGISTICA . ") as logistic_phone,
        RTRIM(" . Agenda::FAX_LOGISTICA . ") as logistic_fax,
        RTRIM(" . Agenda::EMAIL_LOGISTICA . ") as logistic_email,
        RTRIM(" . Agenda::CONTACTO_LOGISTICA . ") as logistic_contact,
		RTRIM(" . Agenda::ABC . ") as abc
        FROM " . Agenda::TABLE . " a 
        INNER JOIN " . Vat::TABLE . " v ON a." . Agenda::CODIGO_PAIS . " = v." . Vat::CODIGO_PAIS . " AND a." . Agenda::NIF . " = v." . Vat::NIF . " 
        INNER JOIN " . Postal::TABLE . " p ON a." . Agenda::CODIGO_PAIS . " = p." . Postal::CODIGO_PAIS . " AND a." . Agenda::CP . " = p." . Postal::CP . " AND a." . Agenda::CODIGO_CIUDAD . " = p." . Postal::CODIGO_CIUDAD . "
        WHERE 1=1 AND RTRIM(cliNom) != '' " . $t;

        /*
                ".Agenda::CODIGO_PAIS_BANCO_CLIENTE." as bank_country_id_customer, 
                ".Agenda::CODIGO_BANCO_CLIENTE." as bank_code_customer, 
                RTRIM(".Agenda::NOMBRE_BANCO_CLIENTE.") as bank_name_customer, 
                ".Agenda::CODIGO_AGENCIA_BANCO_CLIENTE." as bank_agency_code_customer, 
                ".Agenda::CODIGO_CONTROL_AGENCIA_BANCO_CLIENTE." as bank_agency_control_code_customer, 
                RTRIM(".Agenda::CUENTA_BANCARIA_CLIENTE.") as bank_account_customer,
                RTRIM(".Agenda::IBAN_CLIENTE.") as iban_customer,
                ".Agenda::CODIGO_PAIS_BANCO_PROVEEDOR." as bank_country_id_supplier, 
                ".Agenda::CODIGO_BANCO_PROVEEDOR." as bank_code_supplier, 
                RTRIM(".Agenda::NOMBRE_BANCO_PROVEEDOR.") as bank_name_supplier, 
                ".Agenda::CODIGO_AGENCIA_BANCO_PROVEEDOR." as bank_agency_code_supplier, 
                ".Agenda::CODIGO_CONTROL_AGENCIA_BANCO_PROVEEDOR." as bank_agency_control_code_supplier, 
                RTRIM(".Agenda::CUENTA_BANCARIA_PROVEEDOR.") as bank_account_supplier,
                RTRIM(".Agenda::IBAN_PROVEEDOR.") as iban_supplier,  
                */

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;
    }

    public function getCompanies()
    {
        $sql = "
        SELECT " . Compania::HOLDINGCODE . " as holding, " .
            Compania::ID . " as id, 
        RTRIM(" . Compania::NOMBREFISCAL . ") as nameFiscal,  
        RTRIM(" . Compania::NOMBRECOMERCIAL . ") as nameComercial, " .
            Compania::CODIGOPAIS . " as countryId, 
        RTRIM(" . Compania::VAT . ") as cif, 
        RTRIM(" . Compania::DIRECCION . ") as address, 
        RTRIM(" . Compania::CP . ") as zip, 
        RTRIM(" . Compania::CIUDAD . ") as city, 
        RTRIM(" . Compania::REGMERCANTIL . ") as regMercantil, 
        RTRIM(" . Compania::TELEFONO1 . ") as phone1, 
        RTRIM(" . Compania::TELEFONO2 . ") as phone2, 
        RTRIM(" . Compania::FAX . ") as fax, 
        RTRIM(" . Compania::EMAIL . ") as email, 
        RTRIM(" . Compania::WEB . ") as web 
        FROM " . Compania::TABLE . " 
        WHERE 1=1 AND " . Compania::HOLDINGCODE . " = 0";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;

    }

    public function getCenters()
    {
        $sql = " 
        SELECT " . Centro::HOLDINGCODE . " as holding, 
        " . Centro::ID . " as id, 
        " . Centro::EMPRESA . " as companyCode, 
        c." . Centro::CODIGOPAIS . " as countryId, 
        RTRIM(c." . Centro::COGIGOPAISISO . ") as countryIdISO, 
        RTRIM(" . Centro::NOMBRE . ") as name, 
        RTRIM(" . Centro::DIRECCION . ") as address, 
        RTRIM(c." . Centro::CP . ") as zip, 
        c." . Centro::CODIGO_CIUDAD . " as city_id, 
        RTRIM(p." . Postal::NOMBRE_CIUDAD . ") as city_name, 
        RTRIM(" . Centro::TELEFONO . ") as phone, 
        RTRIM(" . Centro::FAX . ") as fax, 
        RTRIM(" . Centro::EMAIL . ") as email
        FROM " . Centro::TABLE . " c
        INNER JOIN " . Postal::TABLE . " p ON c." . Centro::CODIGOPAIS . " = p." . Postal::CODIGO_PAIS . " AND c." . Centro::CP . " = p." . Postal::CP . " AND c." . Centro::CODIGO_CIUDAD . " = p." . Postal::CODIGO_CIUDAD . "
        WHERE 1=1 AND " . Centro::HOLDINGCODE . " = 0 AND " . Centro::EMPRESA . " in (1,2)";

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;
    }


    public function getCategories()
    {
        $sql = " 
        SELECT " . Categoria::ID . " as id, 
        RTRIM(" . Categoria::NOMBRE . ") as name 
        FROM " . Categoria::TABLE;

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;
    }

    public function getSections()
    {
        $sql = " 
        SELECT " . Seccion::ID . " as id, 
        RTRIM(" . Seccion::NOMBRE . ") as name,
        RTRIM(" . Seccion::NIVEL1 . ") as level1,
        RTRIM(" . Seccion::NIVEL2 . ") as level2,
        RTRIM(" . Seccion::NIVEL3 . ") as level3, 
        RTRIM(" . Seccion::NIVEL4 . ") as level4, 
        RTRIM(" . Seccion::NIVEL5 . ") as level5, 
        RTRIM(" . Seccion::NIVEL6 . ") as level6, 
        RTRIM(" . Seccion::NIVEL7 . ") as level7, 
        RTRIM(" . Seccion::NIVEL8 . ") as level8, 
        RTRIM(" . Seccion::NIVEL9 . ") as level9 
        FROM " . Seccion::TABLE;

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;
    }

    public function getSectors()
    {
        $sql = " 
        SELECT " . Sector::ID . " as id, 
        RTRIM(" . Sector::NOMBRE . ") as name
        FROM " . Sector::TABLE;

        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;
    }

    public function getTotalInvoicedAgenda($params)
    {
        $c = '';
        if (isset($params['agenda_id'])) {
            if (!empty($params['agenda_id'])) {
                $c = " AND r.".Registro::CODIGO_AGENDA." = " . $params['agenda_id'];
            }
        }
        $df = '';
        if (isset($params['date_from'])) {
            if (!empty($params['date_from'])) {
                $dAux = parseDate($params['date_from']);
                $df = " AND r.".Registro::FECHA_EMISION." > '" .$dAux ."'";
            }
        }

        $de = '';
        if (isset($params['date_end'])) {
            if (!empty($params['date_end'])) {
                $dAux = parseDate($params['date_end']);
                $de = " AND r.".Registro::FECHA_EMISION." < '" .$dAux ."'";
            }
        }

        //regEsta = 9 -> Finalitzada
        $sql = "
        SELECT 
        r.".Registro::CODIGO_AGENDA." as agenda_id,
        sum(v.".Vencimiento::IMPORTE.") as totalAmount
        FROM ".Registro::TABLE." r 
        INNER JOIN ".Vencimiento::TABLE." v ON v.".Vencimiento::ID_REGISTRO." = r.".Registro::ID." 
                                            AND v.".Vencimiento::CODIGO_EMPRESA." = r.".Registro::CODIGO_EMPRESA." 
                                            AND v.".Vencimiento::CODIGO_EMPRESA." = r.".Registro::CODIGO_EMPRESA." 
                                            AND v.".Vencimiento::TIPO_REGISTRO." = r.".Registro::TIPO_REGISTRO." 
                                            AND v.".Vencimiento::SERVICIO_REGISTRO." = r.".Registro::SERVICIO_REGISTRO." 
        WHERE r.".Registro::ESTADO_REGISTRO." = 9
        AND r.".Registro::CODIGO_HOLDING." = 0 AND r.".Registro::CODIGO_EMPRESA." in (1,2)
        " . $c . $df . $de . "
        GROUP BY r.".Registro::CODIGO_AGENDA;


        $stm = $this->conn->query($sql, PDO::FETCH_ASSOC);
        $rows = $stm->fetchAll();

        return $rows;

    }

}

?>