import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MomentDateAdapter, MAT_MOMENT_DATE_ADAPTER_OPTIONS } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import * as _moment from 'moment';
import { formatDate } from '@angular/common';

export const MY_FORMATS = {
  parse: { dateInput: 'LL' },
  display: {
    dateInput: 'DD-MM-YYYY',
    monthYearLabel: 'YYYY',
    dateA11yLabel: 'LL',
    monthYearA11yLabel: 'YYYY'
  }
};

@Component({
  selector: 'app-dialog-giroconto',
  templateUrl: './dialog-giroconto.component.html',
  styleUrls: ['./dialog-giroconto.component.css'],
  providers: [
    {
      provide: DateAdapter,
      useClass: MomentDateAdapter,
      deps: [MAT_DATE_LOCALE, MAT_MOMENT_DATE_ADAPTER_OPTIONS]
    },
    { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS }
  ]
})
export class DialogGirocontoComponent implements OnInit {
  constructor(
    public dialogRef: MatDialogRef<DialogGirocontoComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any
  ) { }

  ngOnInit() {
    if (!this.data.dataRegistrazione) {
      this.data.dataRegistrazione = new Date();
    }
  }

  getDataFormattata() {
    return formatDate(this.data.dataRegistrazione, 'YYYY-MM-dd', 'en-GB');
  }
}
