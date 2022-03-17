import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { ListBean, ValueBean, VistaAnalisiCommessa } from '../_models';
import { HttpCrudService } from './HttpCrudService';

@Injectable({ providedIn: 'root' })
export class AnalisiCommesseService implements HttpCrudService<VistaAnalisiCommessa> {
  constructor(private http: HttpClient) { }

  getAll(parameters: any): Observable<ListBean<VistaAnalisiCommessa>> {
    return this.http.get<ListBean<VistaAnalisiCommessa>>(environment.wsUrl
                          + `VistaAnalisiCommessa.php?codCommessa=${parameters.codCommessa}`);
  }

  getAllAggregata(parameters: any): Observable<ListBean<VistaAnalisiCommessa>> {
    return this.http.get<ListBean<VistaAnalisiCommessa>>(environment.wsUrl
                          + `VistaAnalisiCommessa.php?codCommessa=${parameters.codCommessa}&aggregato=true`);
  }

  create(obj: VistaAnalisiCommessa): Observable<ValueBean<VistaAnalisiCommessa>> {
    throw new Error('Method not implemented.');
  }
  update(obj: VistaAnalisiCommessa): Observable<ValueBean<VistaAnalisiCommessa>> {
    throw new Error('Method not implemented.');
  }
  delete(obj: VistaAnalisiCommessa): Observable<void> {
    throw new Error('Method not implemented.');
  }
}
