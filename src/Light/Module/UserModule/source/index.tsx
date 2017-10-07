/// <reference path="../../../public/js/lightbuild.d.ts" />

import * as React from 'react';
import * as ReactDOM from 'react-dom';

import UserModuleStore from './UserModuleStore';
import App from './components/App';

import {useStrict} from 'mobx';
import {Provider} from "mobx-react";

useStrict(true);

document.getElementById('page-head')!.remove();
document.getElementById('flash-messages')!.remove();

const store = new UserModuleStore();

Light.listener.simple_combo("ctrl s", () => {
    store.save();
});

ReactDOM.render(<Provider store={store}><App /></Provider>, document.getElementById('app')!);