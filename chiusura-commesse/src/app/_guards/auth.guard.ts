import { CanActivate, Router } from '@angular/router';
import { Injectable } from '@angular/core';
import { AuthenticationService } from '../_services/authentication.service';

/**
 * Questo 'guard' verifica semplicemente che l'utente sia loggato
 */
@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  constructor(
    protected router: Router,
    protected authenticationService: AuthenticationService) {
    // do nothing
  }

  canActivate() {
    if (this.authenticationService.isAuthenticated() === true) {
      return true;
    }

    // not logged in so redirect to login page with the return url
    this.router.navigate(['/login']);
    return true;
  }
}
