export interface VistaCruscotto {
  COD_COMMESSA: string;
  DES_COMMESSA: string;
  COD_DIVISIONE: string;
  DES_DIVISIONE: string;
  COD_CLIENTE: string;
  CLI_RA_SOC: string;
  TOT_FATTURATO: number;
  CONTO_TRANSITORIO: string;
  DES_CONTO_TRANSITORIO: string;
  SALDO_CONTO_TRANSITORIO: number;
  CONTO_RICAVI: string;
  DES_CONTO_RICAVI: string;
  SALDO_CONTO_RICAVI: number;
  CENTRO_COSTO: string;
  TIPO?: number;
}
