export interface VistaCruscotto {
  COD_COMMESSA: string;
  DESCRIZIONE: string;
  COD_CLIENTE: string;
  COD_DIVISIONE: string;
  TOT_FATTURATO: number;
  SALDO_CONTO_TRANSITORIO: number;
  SALDO_CONTO_RICAVI: number;
  TIPO?: number;
}
