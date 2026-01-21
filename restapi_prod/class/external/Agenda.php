<?php

class Agenda
{
    const TABLE = 'AGENDA1';
    const HOLDINGCODE = 'HolCod';
    const ID = 'CliCod';
    const NOMBRE_FISCAL = 'CliDisNom';
    const NOMBRE = 'cliNom';
    const ES_CLIENTE = 'CliSNCli';
    const ES_PROVEEDOR = 'CliSNPro';
    const NIF = 'VatCod';
    const SECTOR = "SecTorCod";
    const CATEGORIA = "CatCod";
    const EMPRESA_ID = "CliClEmp";
    const CUENTA_CLIENTE = "CliCtaPgc";
    const CUENTA_PROVEEDOR = "ProCtaPgc";
    const DIRECION = "CliDir";
    const CODIGO_PAIS = "PaiCod";
    const CP = "PosCod";
    const CODIGO_CIUDAD = "PosRep";
    const APARTADO_CORREOS = "CliApaCor";
    const TELEFONO_TRAFICO = "CliTelTr";
    const FAX_TRAFICO = "CliFaxTr";
    const EMAIL_TRAFICO = "CliMaiTr";
    const CONTACTO_TRAFICO = "CliConTr";
    const TELEFONO_ADMINISTRACION = "CliTelAd";
    const FAX_ADMINISTRACION = "CliFaxAd";
    const EMAIL_ADMINISTRACION = "CliMaiAd";
    const CONTACTO_ADMINISTRACION = "CliConAd";
    const TELEFONO_COMERCIAL = "CliTelCo";
    const FAX_COMERCIAL = "CliFaxCo";
    const EMAIL_COMERCIAL = "CliMaiCo";
    const CONTACTO_COMERCIAL = "CliConCo";
    const OBSERVACIONES = "AgeFilV01";
    //const CODIGO_PAIS_BANCO_CLIENTE = "CliBanPai";
    const CODIGO_BANCO_CLIENTE = "CliBanCod";
    const NOMBRE_BANCO_CLIENTE = "CliBanDes";
    //const CODIGO_AGENCIA_BANCO_CLIENTE = "CliAgeCod";
    //const CODIGO_CONTROL_AGENCIA_BANCO_CLIENTE = "CliAgeCtr";
    //const CUENTA_BANCARIA_CLIENTE = "CliAgeCta";
    const IBAN_CLIENTE = "CliIcoDes";
    //const CODIGO_PAIS_BANCO_PROVEEDOR = "ProBanPai";
    const CODIGO_BANCO_PROVEEDOR = "ProBanCod";
    const NOMBRE_BANCO_PROVEEDOR = "ProBanDes";
    //const CODIGO_AGENCIA_BANCO_PROVEEDOR = "ProAgeCod";
    //const CODIGO_CONTROL_AGENCIA_BANCO_PROVEEDOR = "ProAgeCtr";
    //const CUENTA_BANCARIA_PROVEEDOR = "ProAgeCta";
    const IBAN_PROVEEDOR = "CliTrades";
    const NOMBRE_LOGISTICA = "CliDisNom";
    const DIRECCION_LOGISTICA = "CliDisDom";
    const CODIGO_PAIS_LOGISTICA = "CliDisPai";
    const ISO_PAIS_LOGISTICA = "CliDisNem";
    const CP_LOGISTICA = "CliDisPos";
    const CIUDAD_LOGISTICA = "CliDisPob";
    const RECOGIDA1_LOGISTICA = "CliObsRe1";
    const RECOGIDA2_LOGISTICA = "CliObsRe2";
    const ENTREGA1_LOGISTICA = "CliObsEn1";
    const ENTREGA2_LOGISTICA = "CliObsEn2";
    const CENTRO_PROPIETARIO = "CliCtrPro";
    const CENTRO_DISTRIBUCION = "CliCtrFax";
    const AREA_DISTRIBUCION_ENTREGA = "CliDisAre";
    const AREA_DISTRIBUCION_RECOGIDA = "CliRieTolP";
    const TELF_LOGISTICA = "CliDisTel";
    const FAX_LOGISTICA = "CliDisFax";
    const EMAIL_LOGISTICA = "CliDisMai";
    const CONTACTO_LOGISTICA = "CliDisCon";
    const PADRE_ID = "CliClCod";
    const PADRE_NOMBRE = "CliClNom";
    const ABC = "CliABCCli";

}

class Vat {
    const TABLE = "VAT";
    const CODIGO_PAIS = "PaiCod";
    const NOMBRE_FISCAL = "VatNomFis";
    const NIF = "VatCod";
}
?>