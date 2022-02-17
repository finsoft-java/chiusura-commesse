import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { Router } from '@angular/router';
import { VistaCruscotto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { AzioniService } from '../_services/azioni.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-cruscotto',
  templateUrl: './cruscotto.component.html',
  styleUrls: ['./cruscotto.component.css']
})
export class CruscottoComponent implements OnInit {
  displayedColumns: string[] = ['commessa', 'divisione', 'cliente', 'fatturato',
    'contoTransitorio', 'saldoTr', 'contoRicavi', 'saldoRicavi', 'warning', 'actions'];
  dataSource = new MatTableDataSource<VistaCruscotto>();
  utentePrivilegiato = true;
  warnings = [
    '',
    '',
    'Giroconto parziale',
    'Squadratura',
    'Verificare conto'
  ];
  REFRESH_DELAY = 60; // refresh every REFRESH_DELAY seconds

  constructor(private router: Router,
    private svc: CruscottoService,
    private azioniSvc: AzioniService,
    private alertService: AlertService) {
  }

  ngOnInit(): void {
    this.getAll();
    setInterval(() => {
      this.getAll();
    }, this.REFRESH_DELAY * 1000);
  }

  getAll(): void {
    this.svc.getAll({}).subscribe(response => {
      response.data.forEach(x => {
        this.validazione(x);
      });
      this.dataSource = new MatTableDataSource<VistaCruscotto>(response.data);
    },
    error => {
      this.alertService.error(error);
    });
  }

  validazione(x: VistaCruscotto) {
    console.log(x);
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

  analisi(row: VistaCruscotto) {
    this.router.navigate(['analisi-commessa', row.COD_COMMESSA]);
  }

  avanzamentoWorkflow(row: VistaCruscotto) {
    if (confirm('Il workflow verrà avanzato in stato "A Ricavo". Procedere?')) {
      this.azioniSvc.avanzamentoWorkflow(row.COD_COMMESSA).subscribe(response => {
        this.getAll();
        this.alertService.success('Stato workflow modificato correttamente.');
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  giroconto(row: VistaCruscotto) {
    this.router.navigate(['anteprima-giroconto', row.COD_COMMESSA]);
  }
}
