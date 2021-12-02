import { RigaConto } from './RigaConto';

export interface RigaContoAnalitica extends RigaConto {
  articolo: string;
  articoloRif: string;
  centroCosto: string;
}
