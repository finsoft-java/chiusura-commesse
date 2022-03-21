import { Component, OnDestroy, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { VistaCruscotto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { AzioniService } from '../_services/azioni.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-cruscotto',
  templateUrl: './cruscotto.component.html',
  styleUrls: ['./cruscotto.component.css']
})
export class CruscottoComponent implements OnInit, OnDestroy {
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
  timer?: number;
  filtroCommessa: string = '';

  constructor(private router: Router,
    private route: ActivatedRoute,
    private svc: CruscottoService,
    private azioniSvc: AzioniService,
    private alertService: AlertService) {
  }

  ngOnInit(): void {
    this.filtroCommessa = this.route.snapshot.queryParamMap.get('commessa') || '';
    this.getAll();
    this.timer = window.setInterval(() => {
      this.getAll();
    }, this.REFRESH_DELAY * 1000);
  }

  ngOnDestroy(): void {
    window.clearInterval(this.timer);
  }

  getAll(): void {
    this.svc.getAll({ filtroCommessa: this.filtroCommessa }).subscribe(response => {
      this.dataSource = new MatTableDataSource<VistaCruscotto>(response.data);
    },
    error => {
      this.alertService.error(error);
    });
  }

  filtra(): void {
    localStorage.setItem('filtroCommessa', this.filtroCommessa);

    if (this.filtroCommessa) {
      this.router.navigate(['cruscotto'], { queryParams: { commessa: this.filtroCommessa } });
    } else {
      this.router.navigate(['cruscotto']);
    }
    // in questo caso il router cambia l'URL ma non ricarica il componente
    this.getAll();
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
