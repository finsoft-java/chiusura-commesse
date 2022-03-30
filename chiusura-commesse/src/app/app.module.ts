import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { MatMenuModule } from '@angular/material/menu';
import { MatIconModule } from '@angular/material/icon';
import { CUSTOM_ELEMENTS_SCHEMA, NgModule, LOCALE_ID } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { registerLocaleData } from '@angular/common';
import localeIt from '@angular/common/locales/it';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { NgMaterialMultilevelMenuModule } from 'ng-material-multilevel-menu';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatCardModule } from '@angular/material/card';
import { MatStepperModule } from '@angular/material/stepper';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatTableModule } from '@angular/material/table';
import { MatNativeDateModule } from '@angular/material/core';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { NgxMatSelectSearchModule } from 'ngx-mat-select-search';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatRadioModule } from '@angular/material/radio';
import { MatDialogModule } from '@angular/material/dialog';
import { LoginComponent } from './login/login.component';
import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { AlertComponent } from './_components/alert.component';
import { MatEditTableComponent } from './mat-edit-table/mat-edit-table.component';
import { ErrorInterceptor } from './_helpers/error.interceptor';
import { JwtInterceptor } from './_helpers/jwt.interceptor';
import { CruscottoComponent } from './cruscotto/cruscotto.component';
import { AnalisiCommessaComponent } from './analisi-commessa/analisi-commessa.component';
import { AnteprimaGirocontoComponent } from './anteprima-giroconto/anteprima-giroconto.component';
import { DialogGirocontoComponent } from './dialog-giroconto/dialog-giroconto.component';
import { DialogWorkflowComponent } from './dialog-workflow/dialog-workflow.component';

registerLocaleData(localeIt);

@NgModule({
  declarations: [
    AppComponent,
    MatEditTableComponent,
    AlertComponent,
    LoginComponent,
    CruscottoComponent,
    AnalisiCommessaComponent,
    AnteprimaGirocontoComponent,
    DialogGirocontoComponent,
    DialogWorkflowComponent
  ],
  imports: [
    HttpClientModule,
    BrowserModule,
    AppRoutingModule,
    BrowserAnimationsModule,
    NgMaterialMultilevelMenuModule,
    MatIconModule,
    MatMenuModule,
    MatSidenavModule,
    MatCardModule,
    MatInputModule,
    MatButtonModule,
    MatStepperModule,
    FormsModule,
    ReactiveFormsModule,
    MatTableModule,
    MatRadioModule,
    MatNativeDateModule,
    MatPaginatorModule,
    MatSortModule,
    MatFormFieldModule,
    MatSelectModule,
    MatDatepickerModule,
    MatCheckboxModule,
    MatExpansionModule,
    MatAutocompleteModule,
    MatDialogModule,
    NgxMatSelectSearchModule
  ],
  providers: [
    { provide: HTTP_INTERCEPTORS, useClass: ErrorInterceptor, multi: true },
    { provide: HTTP_INTERCEPTORS, useClass: JwtInterceptor, multi: true },
    { provide: LOCALE_ID, useValue: 'it-IT' }
  ],
  bootstrap: [AppComponent],
  schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class AppModule { }
