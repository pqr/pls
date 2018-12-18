import * as React from 'react';
import * as ReactDOM from 'react-dom';

import UserModuleStore from './UserModuleStore';
import App from './components/App';

import {configure} from 'mobx';
import {Provider} from "mobx-react";

configure({ enforceActions: "observed" });

document.getElementById('page-head')!.remove();
document.getElementById('flash-messages')!.remove();

const store = new UserModuleStore();

declare const Light:any;
Light.listener.simple_combo("ctrl s", () => {
    store.save();
});

ReactDOM.render(<Provider store={store}><App /></Provider>, document.getElementById('app')!);

declare const process: any;
declare const require: any;
if (process.env.NODE_ENV === "development") {
    const DevTools = require("mobx-react-devtools").default;
    const devDiv = document.createElement("div");
    document.body.insertBefore(devDiv, null);
    ReactDOM.render(<DevTools />, devDiv);
}
