import * as React from 'react'

interface AddNewBtnProps {
    text:string;
    onClick:() => void;
}

export default class AddNewBtn extends React.PureComponent<AddNewBtnProps, {}> {

    render() {
        return (
            <span
                className="btn-flat waves-effect usermodule__addlink"
                onClick={() => this.props.onClick()}>
                    + Add {this.props.text}
            </span>
        )
    }

}



