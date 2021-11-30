import { Observable } from 'rxjs';

export interface LabelValue {
  label: string;
  value: any;
}

/**
 * DataTable-like column definition structure
 */
export interface ColumnDefinition<T> {
  /** Titolo della colonna */
  title: string;
  /** Nome dell'attributo dell'oggetto. E' obbligatorio ma puo' essere fittizio. */
  data: string;
  /** input type: text/number/date/hidden/select/... oppure combo(=input+select) */
  type?: string;
  /** width style property */
  width?: string;
  /** render function. Puoi usarlo per formattare le date */
  render?: (data: any, row?: T, rowNum?: number, colNum?: number) => string | null;
  /** select options */
  options?: LabelValue[];
  /** funzione per caricare asincronamente le options */
  asyncOptions?: (row?: T) => Observable<LabelValue[]>;
  /** Se true, il campo è abilitato solo in inserimento */
  disabled?: boolean;
  /** Funzione callback per l'evento onChange */
  onChangeCallback?: (event: Event) => void;
}
