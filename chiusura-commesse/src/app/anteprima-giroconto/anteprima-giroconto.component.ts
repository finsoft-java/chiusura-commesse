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
    this.svcAnalisi.getAllAggregata({ codCommessa: this.codCommessa }).subscribe(response => {
      this.prepareDataCoAn(response.data);
    });
  }

  prepareDataCoGe(x: VistaCruscotto) {
    const data: RigaConto[] = [];
    data.push({
      CONTO: x.CONTO_RICAVI,
      DES_CONTO: x.DES_CONTO_RICAVI,
      SEGNO: 'AVERE',
      IMPORTO: x.SALDO_CONTO_TRANSITORIO
    });
    data.push({
      CONTO: x.CONTO_TRANSITORIO,
      DES_CONTO: x.DES_CONTO_TRANSITORIO,
      SEGNO: 'DARE',
      IMPORTO: x.SALDO_CONTO_TRANSITORIO
    });

    this.dataSource = new MatTableDataSource<RigaConto>(data);
  }

  prepareDataCoAn(l: VistaAnalisiCommessa[]) {
    const data: RigaContoAnalitica[] = [];
    const map = [];
    l.forEach(x => {
      // considero solamente le righe dei conti transitori
      if (x.TIPO_CONTO === 'TRANSITORIO' && x.CONTO_RICAVI) {
        const r1: RigaContoAnalitica = {
          CONTO: x.COD_CONTO,
          DES_CONTO: x.DES_CONTO,
          SEGNO: 'AVERE',
          IMPORTO: x.SALDO,
          COD_CLIENTE: x.COD_CLIENTE,
          CLI_RA_SOC: x.CLI_RA_SOC,
          COD_ARTICOLO: x.COD_ARTICOLO,
          DES_ARTICOLO: x.DES_ARTICOLO,
          COD_ARTICOLO_RIF: x.COD_ARTICOLO_RIF,
          DES_ARTICOLO_RIF: x.DES_ARTICOLO_RIF,
          CENTRO_COSTO: x.CENTRO_COSTO,
          COD_DIVISIONE: x.COD_DIVISIONE,
          DES_DIVISIONE: x.DES_DIVISIONE
        };
        data.push(r1);
        const r2: RigaContoAnalitica = {
          CONTO: 'ZZCONTR',
          DES_CONTO: 'Contropartita',
          SEGNO: 'DARE',
          IMPORTO: x.SALDO,
          COD_CLIENTE: '',
          CLI_RA_SOC: '',
          COD_ARTICOLO: '',
          DES_ARTICOLO: '',
          COD_ARTICOLO_RIF: '',
          DES_ARTICOLO_RIF: '',
          CENTRO_COSTO: 'ZZCONTR',
          COD_DIVISIONE: '',
          DES_DIVISIONE: ''
        };
        data.push(r2);
        const r3: RigaContoAnalitica = {
          CONTO: x.CONTO_RICAVI,
          DES_CONTO: x.DES_CONTO_RICAVI || '',
          SEGNO: 'DARE',
          IMPORTO: x.SALDO,
          COD_CLIENTE: x.COD_CLIENTE,
          CLI_RA_SOC: x.CLI_RA_SOC,
          COD_ARTICOLO: x.COD_ARTICOLO,
          DES_ARTICOLO: x.DES_ARTICOLO,
          COD_ARTICOLO_RIF: x.COD_ARTICOLO_RIF,
          DES_ARTICOLO_RIF: x.DES_ARTICOLO_RIF,
          CENTRO_COSTO: x.CENTRO_COSTO,
          COD_DIVISIONE: x.COD_DIVISIONE,
          DES_DIVISIONE: x.DES_DIVISIONE
        };
        data.push(r3);
        const r4: RigaContoAnalitica = {
          CONTO: 'ZZCONTR',
          DES_CONTO: 'Contropartita',
          SEGNO: 'AVERE',
          IMPORTO: x.SALDO,
          COD_CLIENTE: '',
          CLI_RA_SOC: '',
          COD_ARTICOLO: '',
          DES_ARTICOLO: '',
          COD_ARTICOLO_RIF: '',
          DES_ARTICOLO_RIF: '',
          CENTRO_COSTO: 'ZZCONTR',
          COD_DIVISIONE: '',
          DES_DIVISIONE: ''
        };
        data.push(r4);
      } else if (!x.CONTO_RICAVI) {
        console.log('CONTO_RICAVI is null !?!', x);
      }
    });

    this.dataSourceAnal = new MatTableDataSource<RigaContoAnalitica>(data);
  }

  giroconto() {
    if (confirm('Verrà emesso il giroconto. Procedere?')) {
      this.azioniSvc.preparaGiroconto(this.codCommessa).subscribe(response => {
        const numReg = response.value.numRegistrazione;
        this.alertService.success(`Tabelle CM popolate correttamente.
        Procedere con il CM in Panthera selezionando Origine=RIC-COMM.
        Numero registrazione ${numReg}.
        `);
      },
      error => {
        this.alertService.error(error);
      });
    }
  }

  back() {
    if (localStorage.getItem('filtroCommessa')) {
      this.router.navigate(['cruscotto'], { queryParams: { commessa: localStorage.getItem('filtroCommessa') } });
    } else {
      this.router.navigate(['cruscotto']);
    }
  }
}
