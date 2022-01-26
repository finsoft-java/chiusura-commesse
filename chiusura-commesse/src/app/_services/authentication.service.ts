import { Router } from '@angular/router';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Subject } from 'rxjs';
import { map } from 'rxjs/operators';
import { User } from '../_models/index';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AuthenticationService {
    public currentUserSubject: User;
    nameChange: Subject<string> = new Subject<string>();

    constructor(private http: HttpClient, private router: Router) {
      this.currentUserSubject = new User();
    }

    changeUsername(username: string) {
      this.currentUserSubject.username = username;
      this.nameChange.next(username);
    }

    login(username: string, password: string) {
      console.log(environment.wsUrl);
      const url = environment.wsUrl + 'login.php';
      const body = JSON.stringify({username: username,
                                    password: password});
      return this.http.post<any>(url, body).pipe(map(data => {
        if (data) {
          localStorage.setItem('currentUser', data.value.username);
          localStorage.setItem('role', data.value.ruolo);
          this.changeUsername(username);
        } else {
          data = 'bho';
        }
        return data;
      }));
    }

    public isAuthenticated(): boolean {
      const token = localStorage.getItem('currentUser');
      if (token == null || token === undefined) {
        return false;
      }
      this.currentUserSubject.username = token;
      return true;
    }

    logout() {
      localStorage.removeItem('currentUser');
      localStorage.removeItem('role');
      this.router.navigate(['/login']);
    }
}
