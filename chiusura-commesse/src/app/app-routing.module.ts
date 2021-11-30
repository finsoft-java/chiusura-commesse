import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { AuthGuard } from './_guards/auth.guard';
import { CruscottoComponent } from './cruscotto/cruscotto.component';
import { AnalisiCommessaComponent } from './analisi-commessa/analisi-commessa.component';
import { AnteprimaGirocontoComponent } from './anteprima-giroconto/anteprima-giroconto.component';

const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'cruscotto', component: CruscottoComponent, canActivate: [AuthGuard] },
  { path: 'analisi-commessa/:codCommessa', component: AnalisiCommessaComponent, canActivate: [AuthGuard] },
  { path: 'anteprima-giroconto/:codCommessa', component: AnteprimaGirocontoComponent, canActivate: [AuthGuard] },
  { path: '**', redirectTo: 'cruscotto' }];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
