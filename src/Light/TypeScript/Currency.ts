import { getBeautyDate, getPageParam, myParseFloat, toastError, toastSuccess } from "./Light";

interface TodayRates {
    [currencyIso: string]: { RateToRUB: number; ValidDate: string };
}

// Объект с курссами валют по отношению к RUB
// {EUR: {RateToRUB: 75.1234, ValidDate: '2016-04-12'}, USD: {RateToRUB: 65.7890, ValidDate: '2016-04-12'}}
const rates: TodayRates = getPageParam("CurrencyRates") || {};

export function getRate(from: string, to: string = "USD"): number {
    if (from === to) {
        return 1;
    }

    //Если хотя бы одна из валют рубли, то используем и RateToRUB
    if (from === "RUB") {
        //Пользователь хочет перевести рубли в какую-то другую валюту
        //Например, хочет перевести рубли в доллары. В этом случае в toCurrencyIso.RateToRUB будет что-то типа 35.1234
        //А отношение RUB / USD будет соответсвенно 1 / 35.1234
        return 1 / (rates[to] ? rates[to].RateToRUB : 1);
    }

    if (to === "RUB") {
        //Пользователь хочет перевести какую-то валюту в рубли
        //Например, хочет перевести доллары в рубли. В этом случае в fromCurrencyIso.RateToRUB будет что-то типа 35.1234
        //Его и возвращаем
        return rates[from] ? rates[from]["RateToRUB"] : 1;
    }

    // Если перевод никак не связан с рублями
    // Исходную валюту переводим в рубли, затем результат переводим в валюту назначения. Например, мы хотим перевести
    // фунты в евро. 1 фунт это примерно 1.5 евро - т.е. результат getRate('GBP', 'EUR') === 1.5
    // Сначала делаем getRate('GBP', 'RUB') === 100
    // Затем делаем getRate('RUB', 'EUR') === 1/70
    // Перемножаем 100 * (1/70) ~= 1.5!
    return getRate(from, "RUB") * getRate("RUB", to);
}

export function convertValue(value: string | number, from: string, to: string = "USD"): number {
    return myParseFloat(value) * getRate(from, to);
}

const cacheRates = new Map();
const requestsInProgress = new Map();

export function loadRate(from: string, to: string, validDate: string) {
    if (from === to) {
        return new Promise((resolve) => {
            resolve(1);
        });
    }

    const cacheKey = from + to + validDate;
    if (requestsInProgress.has(cacheKey)) {
        // Возвращаем промис, который уже в работе
        return requestsInProgress.get(cacheKey);
    }

    const prom = new Promise((resolve, reject) => {

        if (cacheRates.has(cacheKey)) {
            // По этим валютам на заданную дату курс уже загружался, сразу резолвим
            resolve(cacheRates.get(cacheKey));
            return;
        }

        const url = `?a=Core&ps=GetRate&date=${validDate}&currencyfrom=${from}&currencyto=${to}`;
        $.ajax(url)
            .done((data: any) => {
                const currRate = parseFloat(data);
                if (!currRate) {
                    toastError(`Не удалось загрузить курс ${from}/${to} на ${getBeautyDate(validDate)}`);
                    reject();
                    return;
                }

                // Запоминаем в кеширующем Map, чтобы в будущем уже не запрашивать этот курс
                cacheRates.set(cacheKey, currRate);

                let beautyCurrRate = (currRate < 1 ? 1 / currRate : currRate).toFixed(4);
                toastSuccess(`Курс ${from}/${to}: ${beautyCurrRate}`);

                // Резолвим промис
                resolve(currRate);

            })
            .fail(() => {
                toastError(`Не удалось загрузить курс ${from}/${to} на ${getBeautyDate(validDate)}`);
                reject();
            })
            .always(() => {
                // Удаляем завершившийся запрос из списка тех, что в процессе
                requestsInProgress.delete(cacheKey);
            });
    });

    // Сохраняем промис в списке выполняемых в данный момент, чтобы не запускать повторные запросы на получение
    // тех же самых курсов
    requestsInProgress.set(cacheKey, prom);

    return prom;
}
