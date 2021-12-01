import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { ListBean, ValueBean, VistaCruscotto } from '../_models';
import { HttpCrudService } from './HttpCrudService';

@Injectable({ providedIn: 'root' })
export class CruscottoService implements HttpCrudService<VistaCruscotto> {
  constructor(private http: HttpClient) { }

  getAll(parameters: any): Observable<ListBean<VistaCruscotto>> {
    return this.http.get<ListBean<VistaCruscotto>>(environment.wsUrl + 'VistaCruscotto.php');
  }

  getById(codCommessa: string): Observable<ValueBean<VistaCruscotto>> {
    return this.http.get<ValueBean<VistaCruscotto>>(environment.wsUrl + `VistaCruscotto.php?codCommessa=${codCommessa}`);
  }

  create(obj: VistaCruscotto): Observable<ValueBean<VistaCruscotto>> {
    throw new Error('Method not implemented.');
  }
  update(obj: VistaCruscotto): Observable<ValueBean<VistaCruscotto>> {
    throw new Error('Method not implemented.');
  }
  delete(obj: VistaCruscotto): Observable<void> {
    throw new Error('Method not implemented.');
  }
}
