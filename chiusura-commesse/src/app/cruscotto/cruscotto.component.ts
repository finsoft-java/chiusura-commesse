import { Component, OnDestroy, OnInit } from '@angular/core';
import { MatCheckboxChange } from '@angular/material/checkbox';
import { MatDialog } from '@angular/material/dialog';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { DialogWorkflowComponent } from '../dialog-workflow/dialog-workflow.component';
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
    'contoTransitorio', 'saldoTr', 'contoRicavi', 'saldoRicavi', 'warning', 'statoCommessa', 'actions'];
  dataSource = new MatTableDataSource<VistaCruscotto>();
  utentePrivilegiato = true;
  warnings: any = {
    'verifica.conti': 'Verificare conti',
    'diff.fatturato': 'Differenza con fatturato',
    'giroconto.parziale': 'Giroconto parziale',
    none: ''
  }
  REFRESH_DELAY = 60; // refresh every REFRESH_DELAY seconds
  timer?: number;
  filtroCommessa: string = '';
  filtroIncludeAll = false;

  constructor(private router: Router,
    private route: ActivatedRoute,
    private svc: CruscottoService,
    private azioniSvc: AzioniService,
    private alertService: AlertService,
    public dialogWf: MatDialog) {
  }

  ngOnInit(): void {
    this.utentePrivilegiato = localStorage.getItem('role') === 'readwrite';
    this.filtroCommessa = this.route.snapshot.queryParamMap.get('commessa') || '';
    this.filtroIncludeAll = this.route.snapshot.queryParamMap.get('includeAll') === 'true' || false;
    this.getAll();
    this.timer = window.setInterval(() => {
      this.getAll();
    }, this.REFRESH_DELAY * 1000);
  }

  ngOnDestroy(): void {
    window.clearInterval(this.timer);
  }

  getAll(): void {
    this.svc.getAll({
      filtroCommessa: this.filtroCommessa,
      includeAll: this.filtroIncludeAll
    }).subscribe(response => {
      this.dataSource = new MatTableDataSource<VistaCruscotto>(response.data);
    },
    error => {
      this.alertService.error(error);
    });
  }

  filtra(): void {
    localStorage.setItem('filtroCommessa', this.filtroCommessa);

    if (this.filtroCommessa || this.filtroIncludeAll) {
      this.router.navigate(['cruscotto'], {
        queryParams: {
          commessa: this.filtroCommessa,
          includeAll: this.filtroIncludeAll ? 'true' : undefined
        }
      });
    } else {
      this.router.navigate(['cruscotto']);
    }
    // in questo caso il router cambia l'URL ma non ricarica il componente
    this.getAll();
  }

  changeFiltroAll(evento: MatCheckboxChange) {
    this.filtroIncludeAll = evento.checked;
    localStorage.setItem('filtroAll', evento.checked ? 'true' : 'false');
    this.filtra();
  }

  analisi(row: VistaCruscotto) {
    this.router.navigate(['analisi-commessa', row.COD_COMMESSA]);
  }

  avanzamentoWorkflow(row: VistaCruscotto) {
    const dialogRef = this.dialogWf.open(DialogWorkflowComponent);

    dialogRef.afterClosed().subscribe(ok => {
      if (ok) {
        this.azioniSvc.avanzamentoWorkflow(row.COD_COMMESSA).subscribe(response => {
          this.getAll();
          this.alertService.success('Stato workflow modificato correttamente.');
        },
        error => {
          this.alertService.error(error);
        });
      }
    });
  }

  giroconto(row: VistaCruscotto) {
    this.router.navigate(['anteprima-giroconto', row.COD_COMMESSA]);
  }
}
