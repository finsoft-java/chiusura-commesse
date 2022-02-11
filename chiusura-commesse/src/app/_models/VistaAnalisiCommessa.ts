export interface VistaAnalisiCommessa {
  COD_CONTO: string;
  COD_COMMESSA: string;
  DES_COMMESSA: string;
  COD_DIVISIONE: string;
  COD_CLIENTE: string;
  DES_CLIENTE: string;
  COD_ARTICOLO: string;
  COD_ARTICOLO_RIF: string;
  CENTRO_COSTO: string;
  DARE: number;
  AVERE: number;
  SALDO: number;
  ESERCIZIO: number;
  TIPO_CONTO: 'TRANSITORIO' | 'RICAVI' | null;
  CONTO_RICAVI: string | null;
}
