import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { RigaConto, RigaContoAnalitica, VistaAnalisiCommessa, VistaCruscotto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { AnalisiCommesseService } from '../_services/analisi.commesse.service';
import { AzioniService } from '../_services/azioni.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-anteprima-giroconto',
  templateUrl: './anteprima-giroconto.component.html',
  styleUrls: ['./anteprima-giroconto.component.css']
})
export class AnteprimaGirocontoComponent implements OnInit {
  displayedColumns: string[] = ['conto', 'dareAvere', 'importo'];
  dataSource = new MatTableDataSource<RigaConto>();
  displayedColumnsAnal: string[] = ['conto', 'divisione', 'cliente', 'articolo', 'artRif',
    'centroCosto', 'dareAvere', 'importo'];
  dataSourceAnal = new MatTableDataSource<RigaContoAnalitica>();
  codCommessa!: string;

  constructor(private route: ActivatedRoute,
    private router: Router,
    private svcCruscotto: CruscottoService,
    private svcAnalisi: AnalisiCommesseService,
    private azioniSvc: AzioniService,
    private alertService: AlertService) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.codCommessa = params.codCommessa;
      this.prepareData();
    });
  }

  prepareData() {
    this.svcCruscotto.getById(this.codCommessa).subscribe(response => {
      this.prepareDataCoGe(response.value);
    });
    this.svcAnalisi.getAll({ codCommessa: this.codCommessa }).subscribe(response => {
      this.prepareDataCoAn(response.data);
    });
  }

  prepareDataCoGe(x: VistaCruscotto) {
    const data: RigaConto[] = [];
    data.push({
      CONTO: x.CONTO_RICAVI,
      VERSO: 'AVERE',
      IMPORTO: x.SALDO_CONTO_TRANSITORIO
    });
    data.push({
      CONTO: x.CONTO_TRANSITORIO,
      VERSO: 'DARE',
      IMPORTO: x.SALDO_CONTO_TRANSITORIO
    });

    this.dataSource = new MatTableDataSource<RigaConto>(data);
  }

  prepareDataCoAn(l: VistaAnalisiCommessa[]) {
    const data: RigaContoAnalitica[] = [];
    l.forEach(x => {
      data.push({
        CONTO: x.COD_CONTO,
        VERSO: x.TIPO_CONTO === 'TRANSITORIO' ? 'AVERE' : 'DARE',
        IMPORTO: x.SALDO,
        COD_ARTICOLO: x.COD_ARTICOLO,
        COD_ARTICOLO_RIF: x.COD_ARTICOLO_RIF,
        CENTRO_COSTO: x.CENTRO_COSTO,
        COD_DIVISIONE: x.COD_DIVISIONE
      });
    });

    this.dataSourceAnal = new MatTableDataSource<RigaContoAnalitica>(data);
  }

  giroconto() {
    if (confirm('Verrà emesso il giroconto. Procedere?')) {
      this.azioniSvc.preparaGiroconto(this.codCommessa).subscribe(response => {
        this.router.navigate(['cruscotto', this.codCommessa]);
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  back() {
    this.router.navigate(['cruscotto', this.codCommessa]);
  }
}
