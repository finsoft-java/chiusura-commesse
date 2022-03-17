export interface VistaAnalisiCommessa {
  COD_CONTO: string;
  DES_CONTO: string;
  COD_COMMESSA: string;
  DES_COMMESSA: string;
  COD_DIVISIONE: string;
  DES_DIVISIONE: string;
  COD_CLIENTE: string;
  CLI_RA_SOC: string;
  COD_ARTICOLO: string;
  DES_ARTICOLO: string;
  COD_ARTICOLO_RIF: string;
  DES_ARTICOLO_RIF: string;
  CENTRO_COSTO: string;
  DARE: number;
  AVERE: number;
  SALDO: number;
  ESERCIZIO?: number; // Questo campo manca nella vista aggregata
  TIPO_CONTO: 'TRANSITORIO' | 'RICAVI' | null;
  CONTO_RICAVI: string | null;
  DES_CONTO_RICAVI: string | null;
}
