import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { ListBean, ValueBean, VistaCruscotto } from '../_models';
import { HttpCrudService } from './HttpCrudService';

@Injectable({ providedIn: 'root' })
export class ChiusuraService {
  constructor(private http: HttpClient) { }

  chiusuraContabile(codCommessa: string) {
    return this.http.post<void>(environment.wsUrl + `ChiusuraContabile.php?codCommessa=${codCommessa}`, '');
  }

  preparaGiroconto(codCommessa: string) {
    return this.http.post<void>(environment.wsUrl + `PreparaGiroconto.php?codCommessa=${codCommessa}`, '');
  }
}
