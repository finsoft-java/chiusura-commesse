import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { ValueBean } from '../_models';

@Injectable({ providedIn: 'root' })
export class AzioniService {
  constructor(private http: HttpClient) { }

  avanzamentoWorkflow(codCommessa: string) {
    return this.http.post<void>(environment.wsUrl + `AvanzamentoWorkflow.php?codCommessa=${codCommessa}`, '');
  }

  preparaGiroconto(codCommessa: string, dataRegistrazione: string) {
    const queryParams = new HttpParams()
      .append('codCommessa', codCommessa)
      .append('dataRegistrazione', dataRegistrazione);

    return this.http.post<ValueBean<any>>(environment.wsUrl + 'PreparaGiroconto.php', queryParams);
  }
}
