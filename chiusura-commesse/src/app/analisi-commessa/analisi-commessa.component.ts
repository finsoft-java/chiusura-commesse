import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { VistaAnalisiCommessa, VistaCruscotto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { AnalisiCommesseService } from '../_services/analisi.commesse.service';
import { AzioniService } from '../_services/azioni.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-analisi-commessa',
  templateUrl: './analisi-commessa.component.html',
  styleUrls: ['./analisi-commessa.component.css']
})
export class AnalisiCommessaComponent implements OnInit {
  displayedColumns: string[] = ['esercizio', 'conto', 'centroCosto', 'divisione', 'cliente', 'articolo', 'artRif', 'saldo'];
  dataSource = new MatTableDataSource<VistaAnalisiCommessa>();
  dataSourceAggregata = new MatTableDataSource<VistaAnalisiCommessa>();
  codCommessa!: string;
  datiCruscotto!: VistaCruscotto;
  utentePrivilegiato = true;
  warnings: any = {
    'verifica.conti': 'Verificare conti',
    'diff.fatturato': 'Differenza tra transitorio+ricavi e fatturato',
    'giroconto.parziale': 'Giroconto parziale',
    none: ''
  }

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private svcAnalisi: AnalisiCommesseService,
    private svcCruscotto: CruscottoService,
    private azioniSvc: AzioniService,
    private alertService: AlertService
  ) { }

  ngOnInit(): void {
    this.utentePrivilegiato = localStorage.getItem('role') === 'readwrite';
    this.route.params.subscribe(params => {
      this.codCommessa = params.codCommessa;
      this.svcAnalisi.getAll({ codCommessa: this.codCommessa }).subscribe(response => {
        this.dataSource = new MatTableDataSource<VistaAnalisiCommessa>(response.data);
      },
      error => {
        this.alertService.error(error);
      });
      this.svcAnalisi.getAllAggregata({ codCommessa: this.codCommessa }).subscribe(response => {
        this.dataSourceAggregata = new MatTableDataSource<VistaAnalisiCommessa>(response.data);
      },
      error => {
        this.alertService.error(error);
      });
      this.svcCruscotto.getById(this.codCommessa).subscribe(response => {
        // eslint-disable-next-line prefer-destructuring
        this.datiCruscotto = response.value;
      },
      error => {
        this.alertService.error(error);
      });
    });
  }

  back() {
    this.router.navigate(['cruscotto'], {
      queryParams: {
        commessa: localStorage.getItem('filtroCommessa') ? localStorage.getItem('filtroCommessa') : undefined,
        includeAll: localStorage.getItem('filtroAll') ? 'true' : undefined
      }
    });
  }

  avanzamentoWorkflow(row: VistaCruscotto) {
    if (confirm('Il workflow verrà avanzato in stato "A Ricavo". Procedere?')) {
      this.azioniSvc.avanzamentoWorkflow(row.COD_COMMESSA).subscribe(response => {
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

  getValidazione(): string {
    if (this.datiCruscotto) {
      if (this.warnings[this.datiCruscotto.WARNING!]) {
        return this.warnings[this.datiCruscotto.WARNING!];
      }
      if (this.datiCruscotto.SALDO_CONTO_TRANSITORIO === 0.0) {
        return 'Giroconto non necessario, è possibile avanzare il workflow';
      }
      return 'Nessuna anomalia rilevata, è possibile creare il giroconto';
    }
    return '';
  }
}
