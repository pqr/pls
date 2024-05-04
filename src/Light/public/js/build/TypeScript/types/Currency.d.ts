export interface Rates {
    [currencyIso: string]: {
        RateToRUB: number;
        ValidDate: string;
    };
}
export declare function getRate(from: string, to?: string, manualRates?: Rates): number;
export declare function convertValue(value: string | number, from: string, to?: string, manualRates?: Rates): number;
export declare function loadRate(from: string, to: string, validDate: string): any;
