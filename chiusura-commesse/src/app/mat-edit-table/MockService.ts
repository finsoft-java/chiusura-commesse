import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ListBean, ValueBean } from '../_models';
import { HttpCrudService } from '../_services/HttpCrudService';

/**
 * Implementazione base di HttpCrudService, che non fa nulla
 * Ti serve ad esempio per le tabelle non editabili
 */
@Injectable({ providedIn: 'root' })
export class MockService<T> implements HttpCrudService<T> {
  constructor() {
    // do nothing
  }

  getAll(parameters: any): Observable<ListBean<T>> {
    return new Observable(observer => {
      observer.next({ data: [], count: 0 });
      observer.complete();
    });
  }

  create(obj: T): Observable<ValueBean<T>> {
    return new Observable(observer => {
      observer.next({ value: obj });
      observer.complete();
    });
  }

  update(obj: T): Observable<ValueBean<T>> {
    return new Observable(observer => {
      observer.next({ value: obj });
      observer.complete();
    });
  }

  delete(obj: T): Observable<void> {
    return new Observable(observer => {
      observer.next(undefined);
      observer.complete();
    });
  }
}
