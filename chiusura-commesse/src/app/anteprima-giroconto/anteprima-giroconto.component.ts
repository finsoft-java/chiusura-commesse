import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { RigaConto } from '../_models';
import { AlertService } from '../_services/alert.service';
import { ChiusuraService } from '../_services/chiusura.service';
import { CruscottoService } from '../_services/cruscotto.service';

@Component({
  selector: 'app-anteprima-giroconto',
  templateUrl: './anteprima-giroconto.component.html',
  styleUrls: ['./anteprima-giroconto.component.css']
})
export class AnteprimaGirocontoComponent implements OnInit {
  displayedColumns: string[] = ['conto', 'dareAvere', 'importo'];
  dataSource = new MatTableDataSource<RigaConto>();
  codCommessa!: string;

  constructor(private route: ActivatedRoute,
    private router: Router,
    private svc: CruscottoService,
    private chiusuraSvc: ChiusuraService,
    private alertService: AlertService) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.codCommessa = params.codCommessa;
      this.prepareData();
    });
  }

  prepareData() {
    this.svc.getById(this.codCommessa).subscribe(response => {
      const data: RigaConto[] = [];
      data.push({
        conto: 'XXX_CONTO_RICAVI',
        verso: 'AVERE',
        importo: response.value.SALDO_CONTO_TRANSITORIO
      });
      data.push({
        conto: 'XXX_CONTO_TRANSITORIO',
        verso: 'DARE',
        importo: response.value.SALDO_CONTO_TRANSITORIO
      });

      this.dataSource = new MatTableDataSource<RigaConto>(data);
    });
  }

  giroconto() {
    alert("L'utente deve dare una conferma, poi chiamiamo il webservice");
    this.chiusuraSvc.preparaGiroconto(this.codCommessa).subscribe(response => {
      this.router.navigate(['cruscotto', this.codCommessa]);
    },
    error => {
      this.alertService.error(error);
    });
  }
}
