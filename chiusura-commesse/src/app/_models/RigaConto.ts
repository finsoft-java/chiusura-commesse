export interface RigaConto {
  conto: string;
  importo: number;
  verso: 'DARE'|'AVERE';
  cliente?: string;
  fornitore?: string;
}
