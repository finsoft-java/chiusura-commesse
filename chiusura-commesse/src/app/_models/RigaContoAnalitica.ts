import { RigaConto } from './RigaConto';

export interface RigaContoAnalitica extends RigaConto {
  COD_ARTICOLO: string;
  COD_ARTICOLO_RIF: string;
  CENTRO_COSTO: string;
  COD_DIVISIONE: string;
}
