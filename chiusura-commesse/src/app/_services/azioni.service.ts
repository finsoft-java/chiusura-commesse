import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';

@Injectable({ providedIn: 'root' })
export class AzioniService {
  constructor(private http: HttpClient) { }

  avanzamentoWorkflow(codCommessa: string) {
    return this.http.post<void>(environment.wsUrl + `AvanzamentoWorkflow.php?codCommessa=${codCommessa}`, '');
  }

  preparaGiroconto(codCommessa: string) {
    return this.http.post<void>(environment.wsUrl + `PreparaGiroconto.php?codCommessa=${codCommessa}`, '');
  }
}
