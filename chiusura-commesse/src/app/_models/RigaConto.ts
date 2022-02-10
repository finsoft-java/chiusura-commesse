export interface RigaConto {
  CONTO: string;
  IMPORTO: number;
  VERSO: 'DARE'|'AVERE';
  COD_CLIENTE?: string;
}
