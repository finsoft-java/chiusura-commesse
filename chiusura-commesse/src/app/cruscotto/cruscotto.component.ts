import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Router } from '@angular/router';
import { VistaCruscotto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-cruscotto',
  templateUrl: './cruscotto.component.html',
  styleUrls: ['./cruscotto.component.css']
})
export class CruscottoComponent implements OnInit {
  displayedColumns: string[] = ['commessa', 'descrizione', 'cliente', 'divisione', 'fatturato',
    'saldoTr', 'saldoRic', 'warning', 'actions'];
  dataSource = new MatTableDataSource<VistaCruscotto>();
  utentePrivilegiato = true;

  constructor(private router: Router, private svc: CruscottoService, private alertService: AlertService) {
  }

  ngOnInit(): void {
    this.svc.getAll({}).subscribe(response => {
      response.data.forEach(x => {
        if (x.TOT_FATTURATO !== (x.SALDO_CONTO_RICAVI + x.SALDO_CONTO_TRANSITORIO)) {
          x.TIPO = 4; // c'è qualche problema, l'utente deve correggere in Panthera
        } else if (x.SALDO_CONTO_TRANSITORIO === 0.0) {
          x.TIPO = 1; // non serve giroconto, si può chiudere
        } else if (x.SALDO_CONTO_RICAVI === 0.0) {
          x.TIPO = 2; // serve giroconto, poi diventa di tipo 1
        } else {
          x.TIPO = 3; // serve giroconto e anche una verifica da parte dell'utente, poi diventa di tipo 1
        }
      });
      this.dataSource = new MatTableDataSource<VistaCruscotto>(response.data);
    },
    error => {
      this.alertService.error(error);
    });
  }

  analisi(row: VistaCruscotto) {
    this.router.navigate(['analisi-commessa', row.COD_COMMESSA]);
  }

  chiusura(row: VistaCruscotto) {
    // TODO
  }

  giroconto(row: VistaCruscotto) {
    this.router.navigate(['anteprima-giroconto', row.COD_COMMESSA]);
  }
}
