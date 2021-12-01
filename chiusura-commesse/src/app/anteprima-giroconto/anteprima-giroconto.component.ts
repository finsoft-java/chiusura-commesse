import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AlertService } from '../_services/alert.service';
import { ChiusuraService } from '../_services/chiusura.service';

@Component({
  selector: 'app-anteprima-giroconto',
  templateUrl: './anteprima-giroconto.component.html',
  styleUrls: ['./anteprima-giroconto.component.css']
})
export class AnteprimaGirocontoComponent implements OnInit {
  codCommessa!: string;

  constructor(private route: ActivatedRoute,
    private router: Router,
    private chiusuraSvc: ChiusuraService,
    private alertService: AlertService) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.codCommessa = params.codCommessa;
      // TODO
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
