import { Injectable } from '@angular/core';
import { HttpRequest, HttpHandler, HttpEvent, HttpInterceptor } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { AuthenticationService } from '../_services/authentication.service';

@Injectable()
export class ErrorInterceptor implements HttpInterceptor {
  constructor(private authenticationService: AuthenticationService) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(request).pipe(catchError(err => {
      console.log(err);
      if (err.status === 401) {
      // auto logout if 401 response returned from api
        this.authenticationService.logout();
        location.reload(true);
      }

      // Il messaggio può essere in molti punti diversi, dipende dal browser
      //  Per Firefox è in err.error.error.value
      let msg = (err.error.error && err.error.error.value) || err.error.message || err.message || err.statusText;

      // Qui interveniamo per personalizzare gli errori
      if (!msg) {
        msg = 'Errore sconosciuto';
      } else if (msg.toLowerCase().includes('http failure response') && (!err.status)) {
        // N.B. lo stesso errore avviene se c'è un errore CORS
        msg = 'Impossibile connettersi al server PHP';
      }
      return throwError(msg);
    }));
  }
}
