import * as React from 'react'
//import DevTools from 'mobx-react-devtools';

import List from "./List"
import View from "./View"

export default class App extends React.PureComponent<{},{}> {

    render() {
        return (
            <div className="usermodule__app">
                <div className="usermodule__list">
                    <List />
                </div>
                <div className="usermodule__view">
                    <View />
                </div>
                {/*<DevTools />*/}
            </div>
        )
    }
}
