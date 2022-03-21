import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from 'src/environments/environment';
import { ListBean, ValueBean, VistaCruscotto } from '../_models';
import { HttpCrudService } from './HttpCrudService';

@Injectable({ providedIn: 'root' })
export class CruscottoService implements HttpCrudService<VistaCruscotto> {
  constructor(private http: HttpClient) { }

  getAll(parameters: any): Observable<ListBean<VistaCruscotto>> {
    let queryParams = new HttpParams();
    if (parameters.filtroCommessa) {
      queryParams = queryParams.append('filtroCommessa', parameters.filtroCommessa);
    }
    return this.http.get<ListBean<VistaCruscotto>>(environment.wsUrl + 'VistaCruscotto.php', { params: queryParams }).pipe(
      map(l => {
        if (l.data) {
          l.data.forEach(x => {
            this.validazione(x);
          });
        }
        return l;
      })
    );
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

  validazione(x: VistaCruscotto) {
    console.log('Validating:', x);
    x.TOT_FATTURATO = x.TOT_FATTURATO || 0.0;
    x.SALDO_CONTO_RICAVI = x.SALDO_CONTO_RICAVI || 0.0;
    x.SALDO_CONTO_TRANSITORIO = x.SALDO_CONTO_TRANSITORIO || 0.0;
    x.CONTO_TRANSITORIO = x.CONTO_TRANSITORIO || '';
    x.CONTO_RICAVI = x.CONTO_RICAVI || '';

    if (x.CONTO_TRANSITORIO.includes(';') || x.CONTO_RICAVI === '' || x.CONTO_RICAVI.includes(';')) {
      x.TIPO = 5; // c'è qualche problema (conti non ben determinati), l'utente deve correggere in Panthera
    } else if (x.TOT_FATTURATO !== (x.SALDO_CONTO_RICAVI + x.SALDO_CONTO_TRANSITORIO)) {
      x.TIPO = 4; // c'è qualche problema (squadratura), l'utente deve correggere in Panthera
    } else if (x.SALDO_CONTO_TRANSITORIO === 0.0) {
      x.TIPO = 1; // non serve giroconto, si può chiudere
    } else if (x.SALDO_CONTO_RICAVI === 0.0) {
      x.TIPO = 2; // serve giroconto, poi diventa di tipo 1
    } else {
      x.TIPO = 3; // serve giroconto (parziale) e anche una verifica da parte dell'utente, poi diventa di tipo 1
    }
  }
}
