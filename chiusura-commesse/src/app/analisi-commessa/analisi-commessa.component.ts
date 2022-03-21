import { Component, OnInit } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { ActivatedRoute, Router } from '@angular/router';
import { VistaAnalisiCommessa } from '../_models';
import { AlertService } from '../_services/alert.service';
import { AnalisiCommesseService } from '../_services/analisi.commesse.service';

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

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private svc: AnalisiCommesseService,
    private alertService: AlertService
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.codCommessa = params.codCommessa;
      this.svc.getAll({ codCommessa: this.codCommessa }).subscribe(response => {
        this.dataSource = new MatTableDataSource<VistaAnalisiCommessa>(response.data);
      },
      error => {
        this.alertService.error(error);
      });
      this.svc.getAllAggregata({ codCommessa: this.codCommessa }).subscribe(response => {
        this.dataSourceAggregata = new MatTableDataSource<VistaAnalisiCommessa>(response.data);
      },
      error => {
        this.alertService.error(error);
      });
    });
  }

  back() {
    if (localStorage.getItem('filtroCommessa')) {
      this.router.navigate(['cruscotto', this.codCommessa], { queryParams: { commessa: localStorage.getItem('filtroCommessa') } });
    } else {
      this.router.navigate(['cruscotto', this.codCommessa]);
    }
  }
}
