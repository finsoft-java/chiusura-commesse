import { Component, EventEmitter, Input, OnInit, Output, ViewChild } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator, PageEvent } from '@angular/material/paginator';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { MatEditTableLabels } from './MatEditTableLabels';
import { HttpCrudService } from '../_services/HttpCrudService';
import { ColumnDefinition } from './ColumnDefinition';
import { MockService } from '.';

@Component({
  selector: 'app-mat-edit-table',
  templateUrl: './mat-edit-table.component.html',
  styleUrls: ['./mat-edit-table.component.css']
})
/**
 * Editable Material Table
 * @see https://muhimasri.com/blogs/create-an-editable-dynamic-table-using-angular-material/
 */
export class MatEditTableComponent<T> implements OnInit {
  @Input()
  labels: MatEditTableLabels = {
    add: 'Nuovo',
    edit: 'Modifica',
    undo: 'Annulla',
    delete: 'Elimina',
    save: 'Salva',
    refresh: 'Refresh',
    exportXlsx: 'Export XLSX',
    exportCsv: 'Export CSV'
  };

  @Input()
  columns: ColumnDefinition<T>[] = [];

  @Input()
  editable = true;

  @Input()
  service: HttpCrudService<T> = new MockService<T>();

  @Input()
  pagination: 'client' | 'server' | null = null;
  @Input()
  pageSizeOptions: number[] = [5, 10, 20];

  @Input()
  formattazioneCondizionale?: (row: T) => any; // map of style attributes

  @Output()
  create: EventEmitter<T> = new EventEmitter();
  @Output()
  update: EventEmitter<T> = new EventEmitter();
  @Output()
  delete: EventEmitter<T> = new EventEmitter();
  @Output()
  errorMessage: EventEmitter<any> = new EventEmitter();

  @ViewChild(MatPaginator, { static: true })
  paginator!: MatPaginator;

  // Questi sono i parametri che si aspetta il mat-table:
  dataSource: MatTableDataSource<T> = new MatTableDataSource();
  displayedColumns: string[] = [];

  // Questi sono i parametri del paginator:
  paginatorLength ? = 0;
  pageSize ? = 10;
  pageIndex ? = 0;

  data: T[] = [];
  creating = false;
  editRowNumber = -1;
  oldRow: T = {} as T;
  buttonsEnabled = true;
  filtro: any = {};
  searchValue: any = {};

  ACTIONS_INDEX = '$$actions';
  XLSX_FILE_NAME = 'Export.xlsx';
  XLSX_SHEET_NAME = 'Data';
  CSV_FILE_NAME = 'Export.csv';

  ngOnInit(): void {
    if (this.editable) {
      this.columns.push({
        // Una colonna per le azioni
        data: this.ACTIONS_INDEX,
        title: '',
        type: ''
      });
    }
    this.columns.forEach(x => this.displayedColumns.push(x.data));

    if (this.pagination !== null) {
      this.dataSource.paginator = this.paginator;
    }
    if (this.pagination !== 'server') {
      this.pageIndex = undefined;
      this.pageSize = undefined;
    }

    this.getAll();
  }

  refresh(): void {
    console.log('Refreshing');
    if (this.pagination !== null) {
      this.pageIndex = 0;
    }
    this.getAll();
  }

  handlePageEvent(event: PageEvent): void {
    this.paginatorLength = event.length;
    this.pageSize = event.pageSize;
    this.pageIndex = event.pageIndex;
    this.getAll();
  }

  filter(filter: any): void {
    this.filtro = filter;
    this.getAll();
  }

  getAll(): void {
    console.log('Get all...');
    this.buttonsEnabled = false;
    if (this.pagination === 'server') {
      this.filtro.page = this.pageIndex;
      this.filtro.size = this.pageSize;
    }
    this.service.getAll(this.filtro).subscribe(
      listBean => {
        this.dataSource.data = listBean.data;
        this.data = listBean.data;
        console.log(this.dataSource.data);
        this.buttonsEnabled = true;
        this.paginatorLength = listBean.count;
      },
      error => {
        this.buttonsEnabled = true;
        console.log('Emitting error:', error);
        this.errorMessage.emit(error);
      }
    );
  }

  renderCell(col: ColumnDefinition<T>, row: T, rowNum: number, colNum: number): string | null {
    const x = (row as any)[col.data];
    return col.render ? col.render(x, row, rowNum, colNum) : x;
  }

  onChangeCell(event: Event, col: ColumnDefinition<T>): void {
    if (col.onChangeCallback) {
      col.onChangeCallback(event);
    }
  }

  onSearchChange(row: T, col: ColumnDefinition<T>, data: string): any {
    (row as any)[col.data] = data;

    if (data.length <= 3) {
      // non faccio la chiamata < 3 caratteri
      return;
    }

    if (col.asyncOptions) {
      col.asyncOptions(row).subscribe(
        options => {
          console.log('Received options', options);
          col.options = options;
        }
      );
    }
  }

  beginCreate(): void {
    const newRow = {} as T;
    this.data.unshift(newRow);
    this.dataSource.data = this.data;
    this.creating = true;
    this.beginEdit(0);
  }

  beginEdit(rowNum: number): void {
    this.editRowNumber = rowNum;
    console.log('Editing row', rowNum);
    const row = this.data[rowNum];
    Object.assign(this.oldRow, row);
    this.columns.forEach(
      col => {
        if (col.asyncOptions) {
          col.asyncOptions(row).subscribe(
            options => {
              console.log('Received options', options);
              col.options = options;
            }
          );
        }
      }
    );
    this.columns.filter(col => col.type === 'combo').forEach(
      col => {
        this.searchValue[col.data] = (row as any)[col.data];
      }
    );
  }

  saveRow(rowNum: number): void {
    if (this.creating) {
      this.createRow(rowNum);
    } else {
      this.updateRow(rowNum);
    }
  }

  createRow(rowNum: number): void {
    this.buttonsEnabled = false;
    this.editRowNumber = -1;
    const row = this.data[rowNum];
    console.log(this);

    this.service.create(row).subscribe(
      response => {
        console.log('Emitting create row:', row);
        this.create.emit(row);
        Object.assign(row, response.value);
        this.buttonsEnabled = true;
        this.creating = false;
      },
      error => {
        this.editRowNumber = rowNum;
        console.log('Emitting error:', error);
        this.errorMessage.emit(error);
        this.buttonsEnabled = true;
        // creating remains true
      }
    );
  }

  updateRow(rowNum: number): void {
    this.buttonsEnabled = false;
    this.editRowNumber = -1;
    const row = this.data[rowNum];
    console.log(this);
    this.service.update(row).subscribe(
      response => {
        console.log('Emitting update row:', row);
        this.update.emit(row);
        Object.assign(row, response.value);
        this.buttonsEnabled = true;
      },
      error => {
        this.editRowNumber = rowNum;
        console.log('Emitting error:', error);
        this.errorMessage.emit(error);
        this.buttonsEnabled = true;
      }
    );
  }

  deleteRow(rowNum: number): void {
    // TODO dovrei dare un messaggio di conferma
    this.buttonsEnabled = false;
    this.editRowNumber = -1;
    const row = this.data[rowNum];
    this.service.delete(row).subscribe(
      () => {
        this.data.splice(rowNum, 1);
        this.dataSource.data = this.data;
        console.log('Emitting delete row:', row);
        this.delete.emit(row);
        this.buttonsEnabled = true;
      },
      error => {
        console.log('Emitting error:', error);
        this.errorMessage.emit(error);
        this.buttonsEnabled = true;
      }
    );
  }

  getFormattazioneCondizionale(row: T): any {
    if (this.formattazioneCondizionale) {
      return this.formattazioneCondizionale(row);
    }
    return null;
  }

  undoChange(rowNum: number): void {
    console.log('Undo');
    this.editRowNumber = -1;
    if (this.creating) {
      this.data.splice(rowNum, 1);
      this.dataSource.data = this.data;
    } else {
      const row = this.data[rowNum];
      Object.assign(row, this.oldRow);
    }
    this.creating = false;
  }

  getSheetHeader(): any[] {
    const row: any[] = [];
    this.columns.forEach(col => {
      if (col.data !== this.ACTIONS_INDEX) {
        row.push(col.title);
      }
    });
    return row;
  }

  getSheetDataColumns(): any[] {
    const row: any[] = [];
    this.columns.forEach(col => {
      if (col.data !== this.ACTIONS_INDEX) {
        row.push(col.data);
      }
    });
    return row;
  }

  getSheetMatrix(): any[][] {
    const matrix: any[] = [];
    let rowNum = 0;
    this.data.forEach(row => {
      const matrixRow: any[] = [];
      let colNum = 0;
      this.columns.forEach(col => {
        if (col.data !== this.ACTIONS_INDEX) {
          matrixRow.push(this.renderCell(col, row, rowNum, colNum++));
        }
      });
      matrix.push(matrixRow);
      ++rowNum;
    });
    return matrix;
  }

  createWorksheet(): XLSX.WorkSheet {
    const header = [this.getSheetHeader()];
    // TODO how to style header?!?
    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(header);
    XLSX.utils.sheet_add_aoa(ws, this.getSheetMatrix(), { origin: -1 });
    return ws;
  }

  exportXlsx(): void {
    const ws = this.createWorksheet();
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, this.XLSX_SHEET_NAME);
    XLSX.writeFile(wb, this.XLSX_FILE_NAME);
  }

  exportCsv(): void {
    const ws = this.createWorksheet();
    const csv = XLSX.utils.sheet_to_csv(ws, { FS: ';' });
    const blob = new Blob([csv], { type: 'text/csv;charset=UTF-8' });
    saveAs(blob, this.CSV_FILE_NAME);
  }
}
