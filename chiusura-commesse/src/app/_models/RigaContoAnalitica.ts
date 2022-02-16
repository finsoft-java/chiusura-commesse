import { RigaConto } from './RigaConto';

export interface RigaContoAnalitica extends RigaConto {
  COD_CLIENTE: string;
  CLI_RA_SOC: string;
  COD_ARTICOLO: string;
  DES_ARTICOLO: string;
  COD_ARTICOLO_RIF: string;
  DES_ARTICOLO_RIF: string;
  CENTRO_COSTO: string;
  COD_DIVISIONE: string;
  DES_DIVISIONE: string;
}
